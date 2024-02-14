<?php
namespace Satispay\Satispay\Model;

class Config
{
    private $config;
    private $scopeConfig;
    private $encryptor;
    private $cacheManager;

    public function __construct(
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Cache\Manager $cacheManager
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->cacheManager = $cacheManager;
    }

    public function generateKeys($storeId = "default")
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

        $this->config->saveConfig("payment/satispay/public_key", $generatedPublicKey, $storeId);
        $this->config->saveConfig(
            "payment/satispay/private_key",
            $this->encryptor->encrypt($generatedPrivateKey),
            $storeId
        );
        $this->cacheManager->flush(['config']);
    }

    public function getPublicKey($storeId = "default")
    {
        $publicKey = $this->scopeConfig->getValue("payment/satispay/public_key", $storeId);
        if (empty($publicKey)) {
            $this->generateKeys($storeId);
            $publicKey = $this->scopeConfig->getValue("payment/satispay/public_key", $storeId);
        }

        return $publicKey;
    }

    public function getPrivateKey($storeId = "default")
    {
        $privateKey = $this->scopeConfig->getValue("payment/satispay/private_key", $storeId);
        if (empty($privateKey)) {
            $this->generateKeys($storeId);
            $privateKey = $this->scopeConfig->getValue("payment/satispay/private_key", $storeId);
        }

        return $this->encryptor->decrypt($privateKey);
    }

    public function getSandbox($storeId = "default")
    {
        return $this->scopeConfig->getValue("payment/satispay/sandbox", $storeId);
    }

    public function getKeyId($storeId = "default")
    {
        return $this->scopeConfig->getValue("payment/satispay/key_id", $storeId);
    }

    public function getSandboxKeyId($storeId = "default")
    {
        return $this->scopeConfig->getValue("payment/satispay/sandbox_key_id", $storeId);
    }

    public function getActive($storeId)
    {

        return $this->scopeConfig->getValue(
            "payment/satispay/active",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getFinalizeUnhandledTransactions($storeId)
    {
        return $this->scopeConfig->getValue(
            "payment/satispay/finalize_unhandled_transactions",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getFinalizeMaxHours($storeId)
    {
        return $this->scopeConfig->getValue(
            "payment/satispay/finalize_max_hours",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
