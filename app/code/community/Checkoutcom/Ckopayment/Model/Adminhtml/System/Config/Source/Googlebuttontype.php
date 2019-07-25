<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_GoogleButtonType
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_GoogleButtonType
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
                'value' => 'google-pay-black',
                'label' => 'Black',
            ),
            array(
                'value' => 'google-pay-white',
                'label' => 'White',
            ),
        );
    }
}
