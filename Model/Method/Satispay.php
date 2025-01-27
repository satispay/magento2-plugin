<?php

namespace Satispay\Satispay\Model\Method;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\StoreManagerInterface;
use Satispay\Satispay\Model\Config;
use SatispayGBusiness\Api;

class Satispay extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'satispay';
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Config $config,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
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
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;

        Api::setPublicKey($this->config->getPublicKey());
        Api::setPrivateKey($this->config->getPrivateKey());

        if ($this->config->getSandbox($this->storeManager->getStore()->getWebsiteId())) {
            Api::setKeyId($this->config->getSandboxKeyId($this->storeManager->getStore()->getWebsiteId()));
            Api::setSandbox(true);
        } else {
            Api::setKeyId($this->config->getKeyId($this->storeManager->getStore()->getWebsiteId()));
        }

        Api::setPluginNameHeader('Magento2');
        Api::setPlatformVersionHeader($this->productMetadata->getVersion());
        Api::setTypeHeader('ECOMMERCE-PLUGIN');
    }

    public function refund(InfoInterface $payment, $amount)
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

    /**
     * By returning true, Magento will not send the new order email immediately.
     * This will eventually be done by Satispay during the callback action.
     *
     * @return bool
     */
    public function getOrderPlaceRedirectUrl()
    {
        return true;
    }

    /**
     * Set keyId and sandbox based on store being processed by cron
     *
     * @param $websiteId
     * @return void
     */
    public function setCronConfigurationsByWebsite($websiteId)
    {
        Api::setPublicKey($this->config->getPublicKey());
        Api::setPrivateKey($this->config->getPrivateKey());

        if ($this->config->getSandbox($websiteId)) {
            Api::setKeyId($this->config->getSandboxKeyId($websiteId));
            Api::setSandbox(true);
        } else {
            Api::setKeyId($this->config->getKeyId($websiteId));
            Api::setSandbox(false);
        }

        Api::setPluginNameHeader('Magento2');
        Api::setPlatformVersionHeader($this->productMetadata->getVersion());
        Api::setTypeHeader('ECOMMERCE-PLUGIN');
    }
}
