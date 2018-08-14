<?php
/**
 * Payment Block for CheckoutApiGooglePay, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiGooglePay
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiGooglePay  extends Mage_Payment_Block_Form_Cc
{
    private $_paymentCode = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_GOOGLE_PAY;

    /**
     * Set template for checkout page
     *
     * @version 20160202
     */
    protected function _construct()
    {
        parent::_construct();

        $session = Mage::getSingleton('chargepayment/session_quote');
        $params['method'] = $this->_paymentCode;
        $params['controllerName'] = (string)Mage::app()->getFrontController()->getRequest()->getControllerName();
        $session->setJsCheckoutApiParams($params);

        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapigooglepay.phtml');
    }

    /*
    * Set Google pay image on checkout page payment method section
    */
    public function getMethodLabelAfterHtml()
    {
        if (! $this->hasData('_method_label_html')) {
            $labelBlock = Mage::app()->getLayout()->createBlock('core/template', 'cko-google-pay-method-label', array(
                'template' => 'checkoutapi/chargepayment/payment_method_label.phtml',
                'payment_method_icon' =>  $this->getSkinUrl('images/checkoutApi/google-pay2.png'),
                'payment_method_class' => $this->getMethod()->getCode()
            ));

            $this->setData('_method_label_html', $labelBlock->toHtml());
        }

        return $this->getData('_method_label_html');
    }

    /**
     * Return true if secret key is correct
     *
     * @return bool
     *
     * @version 20160202
     */
    public function isActive() {
        $helper     = Mage::helper('chargepayment');
        $secretKey  = $helper->getConfigData($this->_paymentCode, 'secretkey');

        return !empty($secretKey) ? true : false;
    }

    /**
     * Return Debug mode
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getDebugMode() {
        return Mage::getModel('chargepayment/googlePay')->isDebug();
    }
    
    /**
     * Return public key
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getPublicKey() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'publickey');
    }

    /**
     * Return Payment Mode
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getPaymentMode() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'payment_mode');
    }

    /**
    * Get google pay merchant Id from config
    **/
    public function getGooglePayMerchantId(){
        return Mage::getModel('chargepayment/googlePay')->getGooglePayMerchantId();
    }

    /**
    * Get Payment information to send to Google
    **/
    public function getPaymentInfo() {
        return Mage::getModel('chargepayment/googlePay')->getPaymentInfo();
    }
}
