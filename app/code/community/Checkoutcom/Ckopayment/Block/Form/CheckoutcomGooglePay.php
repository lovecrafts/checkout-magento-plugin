<?php

/**
 * Class Checkoutcom_Ckopayment_Block_Form_CheckoutcomGooglePay
 */
class Checkoutcom_Ckopayment_Block_Form_CheckoutcomGooglePay extends Mage_Payment_Block_Form_Cc
{
    const CONFIG = 'ckopayment/checkoutcomConfig';
    const GOOGLECONFIG = 'ckopayment/checkoutcomApplePay';
    const TEMPLATE = 'checkoutcom/form/checkoutcomgooglepay.phtml';

    /**
     * Set template for checkout page
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate(self::TEMPLATE);
    }

    /**
     * Get Magento model for the config
     *
     * @return mixed
     */
    public function _getConfigModel()
    {
        return Mage::getModel(self::CONFIG);
    }

    /**
     * Get GooglePay button type from admin module config
     */
    public function getGoogleButtonType()
    {
        return $this->_getConfigModel()->getGoogleButtonType();
    }

    /**
     * Get GooglePay merchant identifier from admin module config
     */
    public function getGoogleMerchantIdentifier()
    {
        return $this->_getConfigModel()->getGoogleMerchantIdentifier();
    }

    /**
     * Get GooglePay public key from admin module config
     */
    public function getPublicKey()
    {
        return $this->_getConfigModel()->getPublicKey();
    }

    /**
     * Get GooglePay environment from admin module config
     */
    public function getEnvironment()
    {
        return $this->_getConfigModel()->getEnvironment();
    }

    /**
     * Get payment information
     *
     * @return mixed
     */
    public function getPaymentInfo()
    {
        return Mage::getModel(self::GOOGLECONFIG)->getPaymentInfo();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return Mage::getBaseUrl();
    }
}
