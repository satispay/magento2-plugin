<?php
/*
Satispay Magento2 Plugin
Copyright (C) 2017  Satispay

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

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