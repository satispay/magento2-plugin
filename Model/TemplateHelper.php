<?php
namespace Satispay\Satispay\Model;

use Magento\Framework\App\Helper\Context;

class TemplateHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $config;
    protected $scopeConfig;
    protected $encryptor;

    public function __construct(
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        Context $context
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    public function getPublicKey($storeId = "default")
    {
        return $this->scopeConfig->getValue("payment/satispay/public_key", $storeId);
    }
}
