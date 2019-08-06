<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_PaymentAction
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_PaymentAction
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
                'value' => true,
                'label' =>'Authorise and Capture'
            ),
            array(
                'value' => false,
                'label' => 'Authorise only'
            ),
        );
    }
}
