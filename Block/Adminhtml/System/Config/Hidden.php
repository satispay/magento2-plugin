<?php
namespace Satispay\Satispay\Block\Adminhtml\System\Config;

class Hidden extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue()->unsLabel();
        return parent::render($element);
    }
}
