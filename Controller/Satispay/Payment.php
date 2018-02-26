<?php
namespace Satispay\Satispay\Controller\Satispay;

require_once(dirname(__FILE__).'/../../includes/online-api-php-sdk/init.php');

class Payment extends \Magento\Framework\App\Action\Action {
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
    $lastOrder = $this->_checkoutSession->getLastRealOrder();

    if ($lastOrder->getState() === $lastOrder::STATE_NEW) {
      $checkout = \SatispayOnline\Checkout::create(array(
        'description' => '#'.$lastOrder->getIncrementId(),
        'phone_number' => '',
        'redirect_url' => $this->_url->getUrl('satispay/satispay/redirect'),
        'callback_url' => $this->_url->getUrl('satispay/satispay/callback', array(
          '_query' => 'charge_id={uuid}'
        )),
        'amount_unit' => round($lastOrder->getGrandTotal() * 100),
        'currency' => $lastOrder->getOrderCurrencyCode(),
        'metadata' => array(
          'order_id' => $lastOrder->getId()
        )
      ));
      $this->_redirect($checkout->checkout_url);
    }
  }
}
