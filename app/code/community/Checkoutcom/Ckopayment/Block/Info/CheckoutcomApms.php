<?php

/**
 * Class Checkoutcom_Ckopayment_Block_Info_CheckoutcomApms
 */
class Checkoutcom_Ckopayment_Block_Info_CheckoutcomApms extends Mage_Payment_Block_Info_Cc
{
    /**
     * Retrieve credit card type name
     * @return bool|string
     */
    public function getCcTypeName()
    {
        return false;
    }
}
