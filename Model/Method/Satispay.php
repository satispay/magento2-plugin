<?php
namespace Satispay\Satispay\Model\Method;

class Satispay extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'satispay';
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    private $config;

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
        array $data = [],
        \Satispay\Satispay\Model\Config $config
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
        $this->config = $config;

        \SatispayGBusiness\Api::setPublicKey($this->config->getPublicKey());
        \SatispayGBusiness\Api::setPrivateKey($this->config->getPrivateKey());

        if ($this->config->getSandbox()) {
            \SatispayGBusiness\Api::setKeyId($this->config->getSandboxKeyId());
            \SatispayGBusiness\Api::setSandbox(true);
        } else {
            \SatispayGBusiness\Api::setKeyId($this->config->getKeyId());
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();

        \SatispayGBusiness\Api::setPluginNameHeader('Magento2');
        \SatispayGBusiness\Api::setPlatformVersionHeader($version);
        \SatispayGBusiness\Api::setTypeHeader('ECOMMERCE-PLUGIN');
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        $satispayPayment = \SatispayGBusiness\Payment::create([
          'flow' => "REFUND",
          'amount_unit' => $amount * 100,
          'currency' => $order->getOrderCurrencyCode(),
          "parent_payment_uid" => $payment->getParentTransactionId(),
          'description' => '#'.$order->getIncrementId()
        ]);

        $payment->setTransactionId($satispayPayment->id);

        return $this;
    }
}
