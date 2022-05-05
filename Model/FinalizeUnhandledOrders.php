<?php


namespace Satispay\Satispay\Model;


use Psr\Log\LoggerInterface;

class FinalizeUnhandledOrders
{
    /**
     * Default finalize max hours.
     */
    const DEFAULT_MAX_HOURS = 4;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Satispay\Satispay\Model\Config
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
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Satispay\Satispay\Model\Method\Satispay $satispay
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Satispay\Satispay\Model\Config $config
     * @param FinalizePayment $finalizePaymentService
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Satispay\Satispay\Model\Method\Satispay $satispay,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Satispay\Satispay\Model\Config $config,
        \Satispay\Satispay\Model\FinalizePayment $finalizePaymentService,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
            $rangeStart = $this->getStartDateScheduledTime($storeId);
            $rangeEnd = $this->getEndDateScheduledTime();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('state', \Magento\Sales\Model\Order::STATE_NEW)
                ->addFilter('store_id', $storeId)
                ->addFilter('updated_at', $rangeStart, 'gteq')
                ->addFilter('updated_at', $rangeEnd, 'lteq')
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria);

            /** @var \Magento\Sales\Model\Order $order */
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
     * @param \Magento\Sales\Model\Order $order
     */
    private function processOrder(\Magento\Sales\Model\Order $order)
    {
        $orderId = $order->getEntityId();
        $payment = $order->getPayment();
        $satispayPaymentId = $payment->getLastTransId();
        if(isset($satispayPaymentId)) {
            $satispayPayment = \SatispayGBusiness\Payment::get($satispayPaymentId);
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
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function addCommentToOrder(\Magento\Sales\Model\Order $order)
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
