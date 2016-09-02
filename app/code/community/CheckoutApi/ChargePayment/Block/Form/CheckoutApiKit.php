<?php
/**
 * Payment Block for Checkout Kit, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiKit
 *
 * @version 20160502
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiKit  extends Mage_Payment_Block_Form_Cc
{
    /**
     * @var
     */
    private $_helper;

    /**
     * Set template for checkout page
     *
     * @version 20160502
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapikit.phtml');
        $this->_helper = Mage::helper('chargepayment');
    }

    /**
     * Return true if secret key is correct
     *
     * @return bool
     *
     * @version 20160502
     */
    public function isActive() {
        $secretKey = $this->_helper->getConfigData(CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT, 'secretkey');
        $publicKey = $this->getPublicKey();

        return !empty($secretKey) && !empty($publicKey) ? true : false;
    }

    /**
     * Return Stored Public Key
     *
     * @return mixed
     *
     * @version 20160502
     */
    public function getPublicKey() {
        return $this->_helper->getConfigData(CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT, 'publickey');
    }

    /**
     * Return Debug Mode
     *
     * @return mixed
     *
     * @version 20160502
     */
    public function getDebugMode() {
        return Mage::getModel('chargepayment/creditCardKit')->isDebug();
    }

    /**
     * Return Customer Email
     *
     * @return mixed
     *
     * @version 20160504
     */
    public function getCustomerEmail() {
        return $this->_helper->getCustomerEmail();
    }

    /**
     * Return Checkout.com script
     *
     * @return mixed
     *
     * @version 20160512
     */
    public function getKitJsLibPath() {
        return Mage::helper('chargepayment')->getKitJsPath();
    }

    /**
     * Return js file path
     *
     * @return string
     */
    public function getKitJsPath() {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . 'checkout_api/kit.js';
    }
}