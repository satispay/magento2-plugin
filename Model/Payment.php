<?php
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
    array $data = []
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
    \SatispayOnline\Api::setClient('Magento/'.$version);
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