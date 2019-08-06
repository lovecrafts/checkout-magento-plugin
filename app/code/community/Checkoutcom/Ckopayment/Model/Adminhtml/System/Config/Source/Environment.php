<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_Environment
 *
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_Environment
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
                'value' => 'sandbox',
                'label' =>'Sandbox'
            ),
            array(
                'value' => 'live',
                'label' => 'Live'
            ),
        );
    }
}
