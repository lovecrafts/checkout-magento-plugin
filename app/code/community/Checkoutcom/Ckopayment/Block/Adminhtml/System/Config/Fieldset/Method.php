<?php

/*
 * class Checkoutcom_Ckopayment_Block_Adminhtml_System_config_Fieldset_Method
 */
class Checkoutcom_Ckopayment_Block_Adminhtml_System_Config_Fieldset_Method
    extends Checkoutcom_Ckopayment_Block_Adminhtml_System_Config_Fieldset_Fieldset
{

    /**
     * Return header title part of html for payment solution
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="entry-edit-head collapseable" ><a id="' . $element->getHtmlId()
            . '-head" href="#" onclick="Fieldset.toggleCollapse(\'' . $element->getHtmlId() . '\', \''
            . $this->getUrl('*/*/state') . '\'); return false;">';

        $html .= ' <img src="'.$this->getSkinUrl('images/checkoutcom/logo-optical-green-white.png').'" height="20" style="vertical-align: text-bottom; margin-right: 5px;margin-top: 2px;"/> ';
        $html .= $element->getLegend();

        $html .= '</a></div>';
        return $html;
    }
}
