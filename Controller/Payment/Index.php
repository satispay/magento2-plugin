<?php

namespace Satispay\Satispay\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
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
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Satispay $satispay,
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        Config $config,
        LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order->getState() == $order::STATE_NEW) {
            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
            $this->orderRepository->save($order);
            $satispayPayment = Payment::create([
                "flow" => "MATCH_CODE",
                "amount_unit" => $order->getGrandTotal() * 100,
                "currency" => $order->getOrderCurrencyCode(),
                "external_code" => $order->getIncrementId(),
                "callback_url" => $this->_url->getUrl('satispay/callback/', [
                    "_query" => "payment_id={uuid}"
                ]),
                "redirect_url" => $this->_url->getUrl('satispay/redirect/'),
                "metadata" => [
                    "order_id" => $order->getId(),
                ]
            ]);

            try {
                $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();
            } catch (NoSuchEntityException $e) {
                $currentWebsiteId = 0;
            }

            if ($this->config->isDebugEnabled($currentWebsiteId)) {
                $this->logger->debug('SATISPAY PAYMENT, PAYMENT CREATE: ' . json_encode($satispayPayment));
            }

            $payment = $order->getPayment();
            if (isset($payment)) {
                // Set last transition id as the satispay payment id
                $payment->setLastTransId($satispayPayment->id);
                $this->orderRepository->save($order);
            } else {
                $this->logger->critical("Satispay - Couldn't save transaction id for order: " . $order->getId());
            }
            $this->_redirect($satispayPayment->redirect_url);
        }
    }
}
