<?php
/**
 * Payment Info Block for CheckoutApiApplePay, $_infoBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Info_CheckoutApiApplePay
 *
 * @version 20160203
 */
class CheckoutApi_ChargePayment_Block_Info_CheckoutApiApplePay  extends Mage_Payment_Block_Info_Cc
{
    /**
     * Removed cart type
     *
     * @return string
     */
    public function getCcTypeName()
    {
        return false;
    }
}