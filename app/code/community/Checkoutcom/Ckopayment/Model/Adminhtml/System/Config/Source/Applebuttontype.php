<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_AppleButtonType
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_AppleButtonType
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
                'value' => 'apple-pay-button-text-buy',
                'label' => 'Buy',
            ),
            array(
                'value' => 'apple-pay-button-text-check-out',
                'label' => 'Checkout out',
            ),
            array(
                'value' => 'apple-pay-button-text-book',
                'label' => 'Book',
            ),
            array(
                'value' => 'apple-pay-button-text-donate',
                'label' => 'Donate',
            ),
            array(
                'value' => 'apple-pay-button',
                'label' => 'plain',
            ),
        );
    }
}
