<?php

namespace Satispay\Satispay\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Hidden
 * @package Satispay\Satispay\Block\Adminhtml\System\Config
 */
class Hidden extends Field
{
    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue()->unsLabel();
        return parent::render($element);
    }
}
