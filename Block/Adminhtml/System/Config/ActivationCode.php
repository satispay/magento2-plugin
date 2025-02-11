<?php
namespace Satispay\Satispay\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Satispay\Satispay\Model\Config;

class ActivationCode extends Field
{
    const TEMPLATE = 'Satispay_Satispay::system/config/activation_code.phtml';
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
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

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
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

        try {
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
        } catch (\Exception $e) {
            //base64 error when first loading the page
        }

        return $this->_toHtml();
    }
}
