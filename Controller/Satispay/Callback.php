<?php
namespace Satispay\Satispay\Controller\Satispay;

class Callback extends \Magento\Framework\App\Action\Action {
  protected $_order;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Sales\Model\Order $order,
    \Satispay\Satispay\Model\Payment $payment
  ) {
    parent::__construct($context);
    $this->_order = $order;
  }

  public function execute() {
    $charge = \SatispayOnline\Charge::get($this->getRequest()->getParam('charge_id'));
    $order = $this->_order->load($charge->metadata->order_id);
    
    if ($order->getState() === $order::STATE_NEW) {
      if ($charge->status === 'SUCCESS') {
        $payment = $order->getPayment();
        $payment->setTransactionId($charge->id);
        $payment->setCurrencyCode($charge->currency);
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($charge->amount / 100);
        
        $order->save();
      }
      
      if ($charge->status === 'FAILURE') {
        $payment = $order->getPayment();
        $payment->setTransactionId($charge->id);
        $payment->setCurrencyCode($charge->currency);
        $payment->setIsTransactionClosed(true);
        $payment->setNotificationResult(true);
        $payment->deny(false);

        $order->registerCancellation();
        $order->save();
      }
    }
    
    $this->getResponse()->setBody('OK');
  }
}
