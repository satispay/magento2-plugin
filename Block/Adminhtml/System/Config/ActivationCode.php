<?php
namespace Satispay\Satispay\Block\Adminhtml\System\Config;

class ActivationCode extends \Magento\Config\Block\System\Config\Form\Field
{
    const TEMPLATE = 'Satispay_Satispay::system/config/activation_code.phtml';

    private $config;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Satispay\Satispay\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return $this;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $endpoint = "https://online.satispay.com";
        if ($originalData["sandbox"] == "1") {
            $endpoint = "https://staging.online.satispay.com";
        }
        
        $keyIdFieldName = "key_id";
        if ($originalData["sandbox"] == "1") {
            $keyIdFieldName = "sandbox_key_id";
        }

        $publicKey = $this->config->getPublicKey();
        if (!is_null($publicKey)) {
            $publicKey = base64_encode($publicKey);
        }
        
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
