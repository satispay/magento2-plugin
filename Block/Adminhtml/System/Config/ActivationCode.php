<?php

namespace Satispay\Satispay\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Satispay\Satispay\Model\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ActivationCode
 * @package Satispay\Satispay\Block\Adminhtml\System\Config
 */
class ActivationCode extends Field
{
    const TEMPLATE = 'Satispay_Satispay::system/config/activation_code.phtml';
    const LIVE_API_URL_PATH = 'payment/satispay/api_url';
    const SANDBOX_API_URL_PATH = 'payment/satispay/sandbox_api_url';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeInterface
     */
    private $scopeConfig;

    /**
     * ActivationCode constructor.
     * @param Context $context
     * @param Config $config
     * @param ScopeInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        ScopeInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $endpoint = $this->scopeConfig->getValue(self::LIVE_API_URL_PATH);
        if ($originalData["sandbox"] == "1") {
            $endpoint = $this->scopeConfig->getValue(self::SANDBOX_API_URL_PATH);
        }

        $keyIdFieldName = "key_id";
        if ($originalData["sandbox"] == "1") {
            $keyIdFieldName = "sandbox_key_id";
        }

        $publicKey = base64_encode($this->config->getPublicKey());
        $buttonLabel = __($originalData["button_label"]);

        $this->addData(
            [
                "button_label" => $buttonLabel,
                "endpoint" => $endpoint,
                "key_id_field_name" => $keyIdFieldName,
                "public_key" => $publicKey,
                "element_id" => $element->getId()
            ]
        );

        return $this->_toHtml();
    }
}
