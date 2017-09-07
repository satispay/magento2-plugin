<?php
namespace Satispay\Satispay\Controller\Satispay;

class Redirect extends \Magento\Framework\App\Action\Action {
  protected $_satispayPayment;
  protected $_checkoutSession;
  protected $_logger;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Satispay\Satispay\Model\Payment $satispayPayment,
    \Magento\Checkout\Model\Session $checkoutSession,
    \Psr\Log\LoggerInterface $logger
  ) {
    parent::__construct($context);
    $this->_satispayPayment = $satispayPayment;
    $this->_checkoutSession = $checkoutSession;
    $this->_logger = $logger;
  }

  public function execute() {
    $charge = \SatispayOnline\Charge::get($this->getRequest()->getParam('charge_id'));
    if ($charge->status === 'SUCCESS') {
      $this->_redirect('checkout/onepage/success');
    } else {
      $this->_checkoutSession->restoreQuote();
      $this->_redirect('checkout/cart');
    }
  }
}