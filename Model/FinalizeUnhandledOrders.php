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
     * Order constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Satispay\Satispay\Model\Method\Satispay $satispay
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Satispay\Satispay\Model\Config $config
     * @param FinalizePayment $finalizePaymentService
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
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

    private function processOrder($order)
    {
        $payment = $order->getPayment();
        $satispayPaymentId = $payment->getLastTransId();
        if(isset($satispayPaymentId)) {
            $satispayPayment = \SatispayGBusiness\Payment::get($satispayPaymentId);
            $hasBeenFinalized = $this->finalizePaymentService->finalizePayment($satispayPayment, $order);
            if ($hasBeenFinalized) {
                $this->addCommentToOrder($order);
            }
        }
    }

    private function addCommentToOrder($order)
    {
        if ($order->canComment()) {
            $comment = $order->addStatusHistoryComment(
                'The Satispay Payment has been finalized by custom command line action'
            );
            $this->orderStatusRepository->save($comment);
        }
    }

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
    private function getStartDateScheduledTime($storeCode)
    {
        $now = new \DateTime();
        $scheduledTimeFrame = $this->config->getFinalizeMaxHours($storeCode);
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
