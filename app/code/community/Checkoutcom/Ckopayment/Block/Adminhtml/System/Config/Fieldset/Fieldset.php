<?php

/*
 *class Checkoutcom_Ckopayment_Block_Adminhtml_System_config_Fieldset_Fieldset
 */
class Checkoutcom_Ckopayment_Block_Adminhtml_System_config_Fieldset_Fieldset
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Get config data model
     *
     * @return Mage_Adminhtml_Model_Config_Data
     */
    protected function _getConfigDataModel()
    {
        if (!$this->hasConfigDataModel()) {
            $this->setConfigDataModel(Mage::getSingleton('adminhtml/config_data'));
        }

        return $this->getConfigDataModel();
    }

    /**
     * Return collapse state
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return bool
     */
    protected function _getCollapseState($element)
    {
        $extra = Mage::getSingleton('admin/session')->getUser()->getExtra();
        if (isset($extra['configState'][$element->getId()])) {
            return $extra['configState'][$element->getId()];
        }

        return false;
    }
}