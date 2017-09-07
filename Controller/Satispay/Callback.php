<?php
namespace Satispay\Satispay\Controller\Satispay;

class Callback extends \Magento\Framework\App\Action\Action {
  protected $_order;
  protected $_satispayPayment;
  protected $_logger;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Sales\Model\Order $order,
    \Satispay\Satispay\Model\Payment $satispayPayment,
    \Psr\Log\LoggerInterface $logger
  ) {
    parent::__construct($context);
    $this->_order = $order;
    $this->_satispayPayment = $satispayPayment;
    $this->_logger = $logger;
  }

  public function execute() {
    $charge = \SatispayOnline\Charge::get($this->getRequest()->getParam('charge'));
    $order = $this->_order->load($charge->metadata->orderid);
    
    if ($order->getState() === $order::STATE_NEW) {
      if ($charge->status === 'SUCCESS') {
        $payment = $order->getPayment();
        $payment->setTransactionId($charge->id);
        $payment->setCurrencyCode($charge->currency);
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($charge->amount / 100);
        
        $order->save();
      } else {
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