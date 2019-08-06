<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_FlaggedStatus
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_FlaggedStatus
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
                'value' => 'pending',
                'label' =>'Pending'
            ),
            array(
                'value' => 'suspected_fraud',
                'label' => 'Suspected Fraud'
            ),
        );
    }
}
