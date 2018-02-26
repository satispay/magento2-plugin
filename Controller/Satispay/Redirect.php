<?php
namespace Satispay\Satispay\Controller\Satispay;

class Redirect extends \Magento\Framework\App\Action\Action {
  protected $_checkoutSession;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Checkout\Model\Session $checkoutSession,
    \Satispay\Satispay\Model\Payment $payment
  ) {
    parent::__construct($context);
    $this->_checkoutSession = $checkoutSession;
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
