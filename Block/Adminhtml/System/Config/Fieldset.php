<?php
namespace Satispay\Satispay\Block\Adminhtml\System\Config;

/**
 * Class Fieldset
 * @package Satispay\Satispay\Block\Adminhtml\System\Config
 */
class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    /**
     * @param $element
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element).' with-button';
    }

    /**
     * @param $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading">';

        $htmlId = $element->getHtmlId();

        $html .= '<div class="button-container">';
        $html .= '<button type="button" class="button action-configure" id="'
            . $htmlId
            . '-head" onclick="toggleSatispaySettings.call(this, \''
            . $htmlId
            . '\', \''
            . $this->getUrl('adminhtml/*/state').'\'); return false;">';
        $html .= '<span class="state-closed">'.__('Configure').'</span>';
        $html .= '<span class="state-opened">'.__('Close').'</span>';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="heading">';
        $html .= '<strong>'.$element->getLegend().'</strong>';
        $html .= '<span class="heading-intro">'.$element->getComment().'</span>';
        $html .= '<div class="config-alt"></div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * @param $element
     * @return mixed
     */
    protected function _getExtraJs($element)
    {
        $script = 'require(["jquery", "prototype"], function(jQuery) {
            window.toggleSatispaySettings = function (id, url) {
                var doScroll = false
                Fieldset.toggleCollapse(id, url)
                if ($(this).hasClassName("open")) {
                    $$(".with-button button.button").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName("open")) {
                            $(anotherButton).click()
                            doScroll = true
                        }
                    }.bind(this))
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this))
                    window.scrollTo(pos[0], pos[1] - 45)
                }
            }
        })';

        return $this->_jsHelper->getScript($script);
    }

    /**
     * @param $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * @param $element
     * @return bool
     */
    protected function _isCollapseState($element)
    {
        return false;
    }
}
