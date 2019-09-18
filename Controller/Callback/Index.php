<?php
namespace Satispay\Satispay\Controller\Callback;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Satispay\Satispay\Model\Method\Satispay $satispay
    ) {
        parent::__construct($context);
        $this->order = $order;
    }

    public function execute()
    {
        $satispayPayment = \SatispayGBusiness\Payment::get($this->getRequest()->getParam("payment_id"));
        $order = $this->order->load($satispayPayment->metadata->order_id);
        
        if ($order->getState() == $order::STATE_NEW) {
            if ($satispayPayment->status == 'ACCEPTED') {
                $payment = $order->getPayment();
                $payment->setTransactionId($satispayPayment->id);
                $payment->setCurrencyCode($satispayPayment->currency);
                $payment->setIsTransactionClosed(true);
                $payment->registerCaptureNotification($satispayPayment->amount_unit / 100, true);

                $order->setState($order::STATE_PROCESSING);
                $order->setStatus($order::STATE_PROCESSING);
                $order->save();
            } elseif ($satispayPayment->status == 'CANCELED') {
                $order->cancel();
            }
        }

        $this->getResponse()->setBody('OK');
    }
}
