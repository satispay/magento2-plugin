<?php

namespace Satispay\Satispay\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class FinalizePayment
{
    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param OrderSender $orderSender
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderSender $orderSender,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderSender = $orderSender;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Finalize a Magento 2 Order Payment following the Satispay Payment Data
     *
     * @param $satispayPayment
     * @param Order $order
     * @return bool
     */
    public function finalizePayment($satispayPayment, Order $order)
    {
        if ($satispayPayment->status == 'ACCEPTED') {
            $payment = $order->getPayment();
            $payment->setTransactionId($satispayPayment->id);
            $payment->setCurrencyCode($satispayPayment->currency);
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($satispayPayment->amount_unit / 100, true);

            $order->setState($order::STATE_PROCESSING);
            $order->setStatus($order::STATE_PROCESSING);
            $this->orderRepository->save($order);

            // Payment is OK: send the new order email
            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
            }
            return true;
        }
        if ($satispayPayment->status == 'CANCELED') {
            $order->registerCancellation(__('Payment received with status CANCELED.'));
            $this->orderRepository->save($order);
            return true;
        }

        return false;
    }
}
