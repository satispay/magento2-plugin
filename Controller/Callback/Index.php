<?php

namespace Satispay\Satispay\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Satispay\Satispay\Model\Config;
use Satispay\Satispay\Model\FinalizePayment;
use Satispay\Satispay\Model\Method\Satispay;
use SatispayGBusiness\Payment;

class Index extends Action
{
    /**
     * @var OrderSender
     */
    protected $orderSender;
    /**
     * @var FinalizePayment
     */
    protected $finalizePaymentService;
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Order $order
     * @param OrderSender $orderSender
     * @param Satispay $satispay
     * @param FinalizePayment $finalizePaymentService
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Order $order,
        OrderSender $orderSender,
        Satispay $satispay,
        FinalizePayment $finalizePaymentService,
        StoreManagerInterface $storeManager,
        Config $config,
        LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->finalizePaymentService = $finalizePaymentService;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute()
    {
        $satispayPayment = Payment::get($this->getRequest()->getParam("payment_id"));

        try {
            $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            $currentWebsiteId = 0;
        }

        if ($this->config->isDebugEnabled($currentWebsiteId)) {
            $this->logger->debug('SATISPAY CALLBACK, PAYMENT GET: ' . json_encode($satispayPayment));
        }

        $order = $this->order->load($satispayPayment->metadata->order_id);

        if ($order->getState() == $order::STATE_NEW || $order->getState() == $order::STATE_PENDING_PAYMENT) {
            $this->finalizePaymentService->finalizePayment($satispayPayment, $order);
        }

        $this->getResponse()->setBody('OK');
    }
}
