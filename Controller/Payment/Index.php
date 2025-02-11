<?php

namespace Satispay\Satispay\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Satispay $satispay,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
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
