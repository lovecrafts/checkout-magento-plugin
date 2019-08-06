<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_AppleButtonTheme
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_AppleButtonTheme
{
    /**
     * Decorate select in System Configuration
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'apple-pay-button-black-with-text',
                'label' => 'Black',
            ),
            array(
                'value' => 'apple-pay-button-white-with-text',
                'label' => 'White',
            ),
            array(
                'value' => 'apple-pay-button-white-with-line-with-text',
                'label' => 'White with outline',
            ),
        );
    }
}
