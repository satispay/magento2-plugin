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

namespace Satispay\Satispay\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod {
  protected $_code = 'satispay_satispay';
  protected $_canRefund = true;
  protected $_canRefundInvoicePartial = true;

  protected $_supportedCurrencies = array('EUR');

  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    \Magento\Payment\Helper\Data $paymentData,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Payment\Model\Method\Logger $logger,
    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    array $data = array()
  ) {
    parent::__construct(
      $context,
      $registry,
      $extensionFactory,
      $customAttributeFactory,
      $paymentData,
      $scopeConfig,
      $logger,
      $resource,
      $resourceCollection,
      $data
    );

    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
    $version = $productMetadata->getVersion();

    \SatispayOnline\Api::setSecurityBearer($this->getConfigData('security_bearer'));
    \SatispayOnline\Api::setStaging((bool)$this->getConfigData('staging'));

    \SatispayOnline\Api::setPluginName('Magento2');
    \SatispayOnline\Api::setType('ECOMMERCE-PLUGIN');
    \SatispayOnline\Api::setPlatformVersion($version);
  }

  public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
    if (!$this->getConfigData('security_bearer')) {
      return false;
    }
    return parent::isAvailable($quote);
  }

  public function canUseForCurrency($currencyCode) {
    if (!in_array($currencyCode, $this->_supportedCurrencies)) {
      return false;
    }
    return true;
  }

  public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    $order = $payment->getOrder();

    \SatispayOnline\Refund::create(array(
      'charge_id' => $payment->getParentTransactionId(),
      'currency' => $order->getOrderCurrencyCode(),
      'amount' => $amount * 100,
      'description' => '#'.$order->getIncrementId()
    ));

    return $this;
  }
}