<?php

/**
 * Class Checkoutcom_Ckopayment_Block_Form_CheckoutcomApplePay
 */
class Checkoutcom_Ckopayment_Block_Form_CheckoutcomApplePay extends Mage_Payment_Block_Form_Cc
{
    const CONFIG = 'ckopayment/checkoutcomConfig';
    const APPLECONFIG = 'ckopayment/checkoutcomApplePay';
    const TEMPLATE = 'checkoutcom/form/checkoutcomapplepay.phtml';

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
     * Get ApplePay merchant identifier from admin module config
     *
     * @return mixed
     */
    public function getMerchantIdentifier()
    {
        return $this->_getConfigModel()->getAppleMerchantIdentifier();
    }

    /**
     * Get ApplePay merchant certificate from admin module config
     *
     * @return mixed
     */
    public function getMerchantCertificate()
    {
        return $this->_getConfigModel()->getAppleCertificate();
    }

    /**
     * Get ApplePay button language from admin module config
     *
     * @return mixed
     */
    public function getAppleButtonLanguage()
    {
        return $this->_getConfigModel()->getAppleButtonLanguage();
    }

    /**
     * Get ApplePay button location from admin module config
     *
     * @return mixed
     */
    public function getAppleButtonLocation()
    {
        return $this->_getConfigModel()->getAppleButtonLocation();
    }

    /**
     * Get ApplePay button type from admin module config
     *
     * @return mixed
     */
    public function getAppleButtonType()
    {
        return $this->_getConfigModel()->getAppleButtonType();
    }

    /**
     * Get ApplePay button theme from admin module config
     *
     * @return mixed
     */
    public function getAppleButtonTheme()
    {
        return $this->_getConfigModel()->getAppleButtonTheme();
    }

    /**
     * Get payment information
     *
     * @return mixed
     */
    public function getPaymentInfo()
    {
        return Mage::getModel(self::APPLECONFIG)->getPaymentInfo();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, array('_secure'=>true));
    }
}
