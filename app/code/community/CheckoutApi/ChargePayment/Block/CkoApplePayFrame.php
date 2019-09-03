<?php
/**
 * Frame for apple pay API
 *
 * Class CheckoutApi_ChargePayment_Block_CkoApplePayFrame
 *
 */
class CheckoutApi_ChargePayment_Block_CkoApplePayFrame  extends Mage_Core_Block_Template
{
    /**
     * Return TRUE if is JS API
     *
     * @return bool
     *
     * @version 20160203
     */
    public function isCkoApplePayPaymentMethod() {
       $paymentMethod = (string)Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethod();

        return $paymentMethod === CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_APPLE_PAY ? true : false;
    }

    /**
     * Return Payment Code
     *
     * @return string
     *
     */
    public function getPaymentCode() {
        return CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_APPLE_PAY;
    }

}