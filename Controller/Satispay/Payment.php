<?php
namespace Satispay\Satispay\Controller\Satispay;

class Payment extends \Magento\Framework\App\Action\Action {
  protected $_checkoutSession;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Checkout\Model\Session $checkoutSession
  ) {
    parent::__construct($context);
    $this->_checkoutSession = $checkoutSession;
  }

  public function execute() {
    $lastOrder = $this->_checkoutSession->getLastRealOrder();
    if ($lastOrder->getState() === $lastOrder::STATE_NEW) {
      $checkout = \SatispayOnline\Checkout::create(array(
        'description' => '#'.$lastOrder->getIncrementId(),
        'phone_number' => $lastOrder->getBillingAddress()->getTelephone(),
        'redirect_url' => $this->_url->getUrl('satispay/satispay/redirect'),
        'callback_url' => $this->_url->getUrl('satispay/satispay/callback', array(
          'charge' => '{uuid}'
        )),
        'amount_unit' => $lastOrder->getGrandTotal() * 100,
        'currency' => $lastOrder->getOrderCurrencyCode(),
        'metadata' => array(
          'orderid' => $lastOrder->getId(),
          'X-Satispay-Client' => \SatispayOnline\Api::getClient()
        )
      ));
      $this->_redirect($checkout->checkout_url);
    }
  }
}