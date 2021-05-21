<?php


namespace Satispay\Satispay\Model;


class FinalizePayment
{
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * Order constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Satispay\Satispay\Model\Method\Satispay $satispay
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Satispay\Satispay\Model\Method\Satispay $satispay
    ) {
        $this->orderSender = $orderSender;
    }

    public function finalizePayment($satispayPayment, $order)
    {
        $hasBeenFinalized = false;

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
            $hasBeenFinalized = true;
        } elseif ($satispayPayment->status == 'CANCELED') {
            $order->registerCancellation(__('Payment received with status CANCELED.'));
            $order->save();
            $hasBeenFinalized = true;
        }

        return $hasBeenFinalized;
    }
}
