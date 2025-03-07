<?php

namespace Satispay\Satispay\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Satispay\Satispay\Model\Config;
use Satispay\Satispay\Model\Method\Satispay;
use SatispayGBusiness\Payment;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
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
     * @param Session $checkoutSession
     * @param Satispay $satispay
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Satispay $satispay,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Config $config,
        LoggerInterface $logger,
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if (!isset($order) || !$order->getId()) {
            // can't collect order from checkout session, payment is still valid and no need to restore cart
            // can't redirect to success page
            $this->_redirect('checkout/cart');
            return;
        }

        $paymentId = $order->getPayment()->getLastTransId();
        $satispayPayment = Payment::get($paymentId);

        try {
            $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            $currentWebsiteId = 0;
        }

        if ($this->config->isDebugEnabled($currentWebsiteId)) {
            $this->logger->debug('SATISPAY REDIRECT, PAYMENT GET: ' . json_encode($satispayPayment));
        }

        if ($satispayPayment->status == 'ACCEPTED') {
            $this->_redirect('checkout/onepage/success');
            return;
        }

        if ($satispayPayment->status == 'PENDING') {

            $satispayCancel = Payment::update($paymentId, [
                'action' => 'CANCEL',
            ]);

            if ($this->config->isDebugEnabled($currentWebsiteId)) {
                $this->logger->debug('SATISPAY REDIRECT, PAYMENT CANCEL: ' . json_encode($satispayPayment));
            }

            if ($satispayCancel->status === 'CANCELED') {
                $order->registerCancellation(__('Payment has been cancelled.'));
                $this->orderRepository->save($order);
                $this->checkoutSession->restoreQuote();
            } else {
                $this->messageManager->addWarningMessage(__('Payment is pending.'));
            }

            $this->_redirect('checkout/cart');

            return;
        }

        $order->registerCancellation(__('Payment has been cancelled.'));
        $this->orderRepository->save($order);
        $this->checkoutSession->restoreQuote();
        $this->messageManager->addWarningMessage(__('Payment has been cancelled.'));
        $this->_redirect('checkout/cart');
    }
}

