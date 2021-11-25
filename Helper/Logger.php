<?php

namespace Satispay\Satispay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Logger
 * @package Satispay\Satispay\Helper
 */
class Logger extends AbstractHelper
{

    const LOG_ENABLE_PATH = 'payment/satispay/enable_log';

    /**
     * @var LoggerInterface
     */
    protected  $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Logger constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $message
     */
    public function logError($message)
    {
        if ($this->scopeConfig->isSetFlag(self::LOG_ENABLE_PATH, ScopeInterface::SCOPE_STORE)) {
            $this->logger->error($message);
        }
    }

    /**
     * @param $message
     */
    public function logInfo($message)
    {
        if ($this->scopeConfig->isSetFlag(self::LOG_ENABLE_PATH, ScopeInterface::SCOPE_STORE)) {
            $this->logger->info($message);
        }
    }
}
