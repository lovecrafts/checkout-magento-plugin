<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_AppleLocation
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_AppleLocation
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
                'value' => 'checkoutpage',
                'label' =>'Checkout Page'
            ),
            array(
                'value' => 'shoppingbasket',
                'label' => 'Shopping Basket'
            ),
            array(
                'value' => 'basketsummary',
                'label' => 'Basket Summary'
            ),
            array(
                'value' => 'productpage',
                'label' => 'Product Page'
            ),
        );
    }
}
