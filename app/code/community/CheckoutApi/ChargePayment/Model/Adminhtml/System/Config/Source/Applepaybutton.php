<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Applepaybutton
 *
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Applepaybutton
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
                'value' => 'logo',
                'label' => 'Logo only'
            ),
            array(
                'value' => 'text',
                'label' => 'Text with logo'
            ),
        );
    }
}