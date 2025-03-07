<?php

namespace Satispay\Satispay\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Satispay\Satispay\Model\Method\Satispay;
use SatispayGBusiness\Payment;

class FinalizeUnhandledOrders
{
    /**
     * Default finalize max hours.
     */
    const DEFAULT_MAX_HOURS = 4;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var FinalizePayment
     */
    protected $finalizePaymentService;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Satispay
     */
    protected $satispay;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Satispay $satispay
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param FinalizePayment $finalizePaymentService
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface              $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        SearchCriteriaBuilder                 $searchCriteriaBuilder,
        Satispay                              $satispay,
        StoreManagerInterface                 $storeManager,
        Config                                $config,
        FinalizePayment                       $finalizePaymentService,
        LoggerInterface                       $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->satispay = $satispay;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->finalizePaymentService = $finalizePaymentService;
        $this->logger = $logger;
    }

    /**
     *  Get list of orders from available stores and process them
     */
    public function finalizeUnhandledOrders()
    {
        $availableStores = $this->getAvailableStores();

        foreach ($availableStores as $storeId) {
            $this->satispay->setCronConfigurationsByWebsite($this->storeManager->getStore($storeId)->getWebsiteId());
            $rangeStart = $this->getStartDateScheduledTime($storeId);
            $rangeEnd = $this->getEndDateScheduledTime();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('state', [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT], 'in')
                ->addFilter('store_id', $storeId)
                ->addFilter('updated_at', $rangeStart, 'gteq')
                ->addFilter('updated_at', $rangeEnd, 'lteq')
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria);

            /** @var Order $order */
            foreach ($orders->getItems() as $order) {
                $orderPayment = $order->getPayment();
                if (isset($orderPayment) && $orderPayment->getMethod() === 'satispay') {
                    try {
                        $this->processOrder($order);
                    } catch (\Exception $e) {
                        $orderId = $order->getEntityId();
                        $this->logger->error("Could not finalize Order $orderId for Satispay payment: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param Order $order
     */
    private function processOrder(Order $order)
    {
        $orderId = $order->getEntityId();
        $payment = $order->getPayment();
        $satispayPaymentId = $payment->getLastTransId();
        if (isset($satispayPaymentId)) {
            $satispayPayment = Payment::get($satispayPaymentId);

            try {
                $currentWebsiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
            } catch (NoSuchEntityException $e) {
                $currentWebsiteId = 0;
            }

            if ($this->config->isDebugEnabled($currentWebsiteId)) {
                $this->logger->debug('SATISPAY CRON, PAYMENT GET: ' . json_encode($satispayPayment));
            }

            $hasBeenFinalized = $this->finalizePaymentService->finalizePayment($satispayPayment, $order);
            if ($hasBeenFinalized) {
                $this->logger->info("The Order $orderId has been finalized for Satispay payment.");
                try {
                    $this->addCommentToOrder($order);
                } catch (\Exception $e) {
                    $this->logger->error("Could not save comment to Order $orderId: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Save a custom comment to the Magento Order
     *
     * @param Order $order
     * @throws CouldNotSaveException
     */
    private function addCommentToOrder(Order $order)
    {
        if ($order->canComment()) {
            $comment = $order->addStatusHistoryComment(
                'The Satispay Payment has been finalized by custom command line action'
            );
            $this->orderStatusRepository->save($comment);
        }
    }

    /**
     * Get available stores enabled for finalize transaction action
     *
     * @return array
     */
    private function getAvailableStores()
    {
        $storeManagerDataList = $this->storeManager->getStores();
        $availableStores = array();

        foreach ($storeManagerDataList as $store) {
            $isFinalizeCronEnabled = $this->config->getFinalizeUnhandledTransactions($store->getId());
            $isSatispayEnabled = $this->config->getActive($store->getId());
            if ($isSatispayEnabled && $isFinalizeCronEnabled) {
                $availableStores[] = $store->getId();
            }
        }
        return $availableStores;
    }

    /**
     * Get the start criteria for the scheduled datetime
     */
    private function getStartDateScheduledTime(int $storeId)
    {
        $now = new \DateTime();
        $scheduledTimeFrame = $this->config->getFinalizeMaxHours($storeId);
        if (!isset($scheduledTimeFrame)) {
            $scheduledTimeFrame = self::DEFAULT_MAX_HOURS;
        }
        $tosub = new \DateInterval('PT'. $scheduledTimeFrame . 'H');
        return $now->sub($tosub)->format('Y-m-d H:i:s');
    }

    /**
     * Get the end criteria for the scheduled datetime
     */
    private function getEndDateScheduledTime()
    {
        $now = new \DateTime();
        // remove just 1 hour so normal transactions can still be processed
        $tosub = new \DateInterval('PT'. 1 . 'H');
        return $now->sub($tosub)->format('Y-m-d H:i:s');
    }
}
