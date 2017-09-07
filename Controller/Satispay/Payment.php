<?php
namespace Satispay\Satispay\Controller\Satispay;

class Payment extends \Magento\Framework\App\Action\Action {
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
    $lastOrder = $this->_checkoutSession->getLastRealOrder();
    if ($lastOrder->getState() === $lastOrder::STATE_NEW) {
      $checkout = \SatispayOnline\Checkout::create(array(
        'description' => '#'.$lastOrder->getIncrementId(),
        'phone_number' => $lastOrder->getBillingAddress()->getTelephone(),
        'redirect_url' => $this->_url->getUrl('satispay/satispay/redirect'),
        'callback_url' => $this->_url->getUrl('satispay/satispay/callback', $this->_redirect->updatePathParams([
          'charge' => '{uuid}'
        ])),
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