<?php

namespace Satispay\Satispay\Model;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class Config
 * @package Satispay\Satispay\Model
 */
class Config
{

    const SATISPAY_PUBLIC_KEY_PATH = "payment/satispay/public_key";
    const SATISPAY_PRIVATE_KEY_PATH = "payment/satispay/private_key";
    const SATISPAY_SANDBOX_PATH = "payment/satispay/sandbox";
    const SATISPAY_KEY_ID_PATH = "payment/satispay/key_id";
    const SATISPAY_SANDBOX_KEY_ID_PATH = "payment/satispay/sandbox_key_id";

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
     * Config constructor.
     * @param ConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $storeId
     */
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

        $this->config->saveConfig(self::SATISPAY_PUBLIC_KEY_PATH, $generatedPublicKey, $storeId);
        $this->config->saveConfig(
            self::SATISPAY_PRIVATE_KEY_PATH,
            $this->encryptor->encrypt($generatedPrivateKey),
            $storeId
        );
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getPublicKey($storeId = "default")
    {
        $publicKey = $this->scopeConfig->getValue(self::SATISPAY_PUBLIC_KEY_PATH, $storeId);
        if (empty($publicKey)) {
            $this->generateKeys($storeId);
            $publicKey = $this->scopeConfig->getValue(self::SATISPAY_PUBLIC_KEY_PATH, $storeId);
        }

        return $publicKey;
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getPrivateKey($storeId = "default")
    {
        $privateKey = $this->scopeConfig->getValue(self::SATISPAY_PRIVATE_KEY_PATH, $storeId);
        if (empty($privateKey)) {
            $this->generateKeys($storeId);
            $privateKey = $this->scopeConfig->getValue(self::SATISPAY_PRIVATE_KEY_PATH, $storeId);
        }

        return $this->encryptor->decrypt($privateKey);
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getSandbox($storeId = "default")
    {
        return $this->scopeConfig->getValue(self::SATISPAY_SANDBOX_PATH, $storeId);
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getKeyId($storeId = "default")
    {
        return $this->scopeConfig->getValue(self::SATISPAY_KEY_ID_PATH, $storeId);
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getSandboxKeyId($storeId = "default")
    {
        return $this->scopeConfig->getValue(self::SATISPAY_SANDBOX_KEY_ID_PATH, $storeId);
    }
}
