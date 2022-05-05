<?php


namespace Satispay\Satispay\Model;


class FinalizePayment
{
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Satispay\Satispay\Model\Method\Satispay $satispay
     */
    public function __construct(
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Satispay\Satispay\Model\Method\Satispay $satispay
    ) {
        $this->orderSender = $orderSender;
    }

    /**
     * Finalize a Magento 2 Order Payment following the Satispay Payment Data
     *
     * @param $satispayPayment
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function finalizePayment($satispayPayment, \Magento\Sales\Model\Order $order)
    {
        if ($satispayPayment->status == 'ACCEPTED') {
            $payment = $order->getPayment();
            $payment->setTransactionId($satispayPayment->id);
            $payment->setCurrencyCode($satispayPayment->currency);
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($satispayPayment->amount_unit / 100, true);

            $order->setState($order::STATE_PROCESSING);
            $order->setStatus($order::STATE_PROCESSING);
            $order->save();

            // Payment is OK: send the new order email
            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
            }
            return true;
        }
        if ($satispayPayment->status == 'CANCELED') {
            $order->registerCancellation(__('Payment received with status CANCELED.'));
            $order->save();
            return true;
        }

        return false;
    }
}
