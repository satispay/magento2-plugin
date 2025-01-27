<?php
namespace Satispay\Satispay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class TemplateHelper extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function getPublicKey()
    {
        return $this->scopeConfig->getValue("payment/satispay/public_key",
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }
}
