<?php
/**
 * Payment Block for CheckoutApiJs, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiJs
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiEmbedded  extends Mage_Payment_Block_Form_Cc
{
    private $_paymentCode = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_EMBEDDED;

    /**
     * Set template for checkout page
     *
     * @version 20160202
     */
    protected function _construct()
    {
        parent::_construct();

        $session            = Mage::getSingleton('chargepayment/session_quote');
        $params['method'] = $this->_paymentCode;
        $params['controllerName'] = (string)Mage::app()->getFrontController()->getRequest()->getControllerName();
        $session->setJsCheckoutApiParams($params);

        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapiembedded.phtml');
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
        return Mage::getModel('chargepayment/creditCardEmbedded')->isDebug();
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
     * Return controller URL
     *
     * @return string
     *
     * @version 20160212
     */
    public function getControllerUrl() {
        $params     = array('form_key' => Mage::getSingleton('core/session')->getFormKey());
        $isSecure   = Mage::app()->getStore()->isCurrentlySecure();

        if ($isSecure){
            $secure = array('_secure' => true);
            $params = array_merge($params, $secure);

        }

        return $this->getUrl('chargepayment/api/place/', $params);
    }

    /**
     * Return Checkout.com script
     *
     * @return mixed
     *
     * @version 20160512
     */
    public function getEmbeddedJsPath() {
        return Mage::helper('chargepayment')->getEmbeddedJsPath();
    }

    /*
     * Check if customer is logged in
     *
     * */

    public function isCustomerLogged() {

        return Mage::getModel('chargepayment/creditCardEmbedded')->getCustomerId();
    }

    /*
     * Get Theme option
     *
     * */

    public function getTheme() {

        return Mage::getModel('chargepayment/creditCardEmbedded')->getTheme();
    }

     /*
     * Get Theme option
     *
     * */

    public function getCustomCssUrl() {

        return Mage::getModel('chargepayment/creditCardEmbedded')->getCustomCssUrl();
    }

    /*
     * return customer's saved cards
     * */
    public function getCustomerCardList() {
        $result         = array();

        $customerId     = Mage::getModel('chargepayment/creditCardEmbedded')->getCustomerId();

        if (empty($customerId)) {
            return $result;
        }

        $cardModel      = Mage::getModel('chargepayment/customerCard');
        $collection     = $cardModel->getCustomerCardList($customerId);

        if (!$collection->count()) {
            return $result;
        }

        foreach($collection as $index => $card) {
            
            if($card->getSaveCard() == ''){
              continue;
            }
            
            $result[$index]['title']    = sprintf('xxxx-%s', $card->getCardNumber());
            $result[$index]['value']    = $cardModel->getCardSecret($card->getId(), $card->getCardNumber(), $card->getCardType());
            $result[$index]['type']     = $card->getCardType();
        }
        return $result;
    }

    /**
    * Get Save Card setting from config
    *
    **/
    public function isSaveCard(){
        return Mage::getModel('chargepayment/creditCardEmbedded')->getSaveCardSetting();
    }

    /**
    * Get Cvv verification setting from config
    *
    **/
    public function cvvVerification(){
        return Mage::getModel('chargepayment/creditCardEmbedded')->getCvvVerification();
    }
}
