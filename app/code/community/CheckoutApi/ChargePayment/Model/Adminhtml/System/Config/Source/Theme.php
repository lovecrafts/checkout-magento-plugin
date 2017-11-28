<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Theme
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Theme
{
    /**
     * Decorate select in System Configuration
     *
     * @return array
     *
     * @version
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => CheckoutApi_ChargePayment_Helper_Data::EMBEDDED_THEME_STANDARD,
                'label' => Mage::helper('chargepayment')->__('Standard')
            ),
            array(
                'value' => CheckoutApi_ChargePayment_Helper_Data::EMBEDDED_THEME_SIMPLE,
                'label' => Mage::helper('chargepayment')->__('Simple')
            ),
        );
    }
}