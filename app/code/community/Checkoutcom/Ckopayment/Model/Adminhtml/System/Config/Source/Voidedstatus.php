<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_VoidedStatus
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_VoidedStatus
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
                'value' => 'dont_change',
                'label' => "Don't Change"
            ),
            array(
                'value' => 'canceled',
                'label' => 'Canceled'
            ),
        );
    }
}
