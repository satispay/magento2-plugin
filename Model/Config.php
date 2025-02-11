<?php
namespace Satispay\Satispay\Model;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @param ConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param Manager $cacheManager
     */
    public function __construct(
        ConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        Manager $cacheManager
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->cacheManager = $cacheManager;
    }

    public function generateKeys()
    {
        $pkeyResource = openssl_pkey_new([
            "digest_alg" => "sha256",
            "private_key_bits" => 2048
        ]);

        openssl_pkey_export($pkeyResource, $generatedPrivateKey);

        // $generatedPrivateKey = str_replace("\n", "", $generatedPrivateKey);
        // $generatedPrivateKey = str_replace("-----BEGIN PRIVATE KEY-----", "", $generatedPrivateKey);
        // $generatedPrivateKey = str_replace("-----END PRIVATE KEY-----", "", $generatedPrivateKey);

        $pkeyResourceDetails = openssl_pkey_get_details($pkeyResource);
        $generatedPublicKey = $pkeyResourceDetails["key"];

        // $generatedPublicKey = str_replace("\n", "", $generatedPublicKey);
        // $generatedPublicKey = str_replace("-----BEGIN PUBLIC KEY-----", "", $generatedPublicKey);
        // $generatedPublicKey = str_replace("-----END PUBLIC KEY-----", "", $generatedPublicKey);

        $this->config->saveConfig(
            "payment/satispay/public_key",
            $generatedPublicKey
        );
        $this->config->saveConfig(
            "payment/satispay/private_key",
            $this->encryptor->encrypt($generatedPrivateKey)
        );

        $this->cacheManager->flush(['config']);
    }

    public function getPublicKey()
    {
        $publicKey = $this->scopeConfig->getValue("payment/satispay/public_key",
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        if (empty($publicKey)) {
            $this->generateKeys();
            $publicKey = $this->scopeConfig->getValue("payment/satispay/public_key",
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }

        return $publicKey;
    }

    public function getPrivateKey()
    {
        $privateKey = $this->scopeConfig->getValue("payment/satispay/private_key",
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if (empty($privateKey)) {
            $this->generateKeys();
            $privateKey = $this->scopeConfig->getValue("payment/satispay/private_key",
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }

        return $this->encryptor->decrypt($privateKey);
    }

    public function getSandbox($websiteId)
    {
        return $this->scopeConfig->getValue("payment/satispay/sandbox",
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    public function getKeyId($websiteId)
    {
        return $this->scopeConfig->getValue("payment/satispay/key_id",
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    public function getSandboxKeyId($websiteId)
    {
        return $this->scopeConfig->getValue("payment/satispay/sandbox_key_id",
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    public function getActive($storeId)
    {
        return $this->scopeConfig->getValue(
            "payment/satispay/active",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getFinalizeUnhandledTransactions($storeId)
    {
        return $this->scopeConfig->getValue(
            "payment/satispay/finalize_unhandled_transactions",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getFinalizeMaxHours($storeId)
    {
        return $this->scopeConfig->getValue(
            "payment/satispay/finalize_max_hours",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
