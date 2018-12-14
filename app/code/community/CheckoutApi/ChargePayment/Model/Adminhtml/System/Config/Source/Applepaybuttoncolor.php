<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Applepaybuttoncolor
 *
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Applepaybuttoncolor
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
                'value' => 'black',
                'label' => 'Black'
            ),
            array(
                'value' => 'white',
                'label' => 'White'
            ),
        );
    }
}