<?php

/**
 * Class for GooglePay payment method
 *
 * Class CheckoutApi_ChargePayment_Model_GooglePay
 *
 */
class CheckoutApi_ChargePayment_Model_GooglePay extends CheckoutApi_ChargePayment_Model_Checkout
{
    protected $_code            = 'checkoutapigooglepay';//CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_GOOGLE_PAY;
    protected $_canUseInternal  = false;

    protected $_formBlockType = 'chargepayment/form_checkoutApiGooglePay';
    protected $_infoBlockType = 'chargepayment/info_checkoutApiGooglePay';


    /**
     * Return to checkout page
     *
     * @return bool|string
     *
     */
    public function getCheckoutRedirectUrl() {}
   
    /**
     * Return redirect url for 3d and local payments
     *
     * @return bool
     */
    public function getOrderPlaceRedirectUrl() {}

    /**
     * Return Quote from session
     *
     * @param null $quoteId
     * @return mixed
     *
     * @version 20160202
     */
    private function _getQuote($quoteId = null) {
        $quoteId = (int)$quoteId;
        if (!empty($quoteId)) {
            return Mage::getModel('sales/quote')->load($quoteId);
        }
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Get Public Shared Key
     *
     * @return mixed
     *
     * @version 20160407
     */
    public function getPublicKeyWebHook() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey_web');
    }

    /**
     * Get Secret Key
     *
     * @return mixed
     *
     * @version 20161910
     */
    public function getSecretKey() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'secretkey');
    }

    /**
     * Get Endpoint Mode
     *
     * @return mixed
     *
     * @version 20161910
     */
    public function getMode() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'mode');
    }

    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     *
     * @version 20160203
     */
    public function validate() {
        return $this;
    }

    /*
    * Get Debug value from module settings
    */
    public function isDebug() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'debug');
    }

    /**
     * For authorize
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     *
     * @version 20160204
     */
    public function authorize(Varien_Object $payment, $amount) {
		// does not create charge on checkout.com if amount is 0
        if (empty($amount)) {
            return $this;
        }

        $requestData        = Mage::app()->getRequest()->getParam('payment');
        $session            = Mage::getSingleton('chargepayment/session_quote');

        if($requestData['method'] == "checkoutapigooglepay"){
            $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
            $result = $this->createGoogleCharge($requestData, $payment);

            $entityId       = $result->id;

            if (preg_match('/^1[0-9]+$/', $result->responseCode)) {
                $payment->setTransactionId($entityId);
                $payment->setIsTransactionClosed(0);
                $payment->setAdditionalInformation('use_current_currency', $isCurrentCurrency);

                if((int)$result->responseCode !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED ){
                    $order->addStatusHistoryComment('Suspected fraud - Please verify amount and quantity.', false);
                    $payment->setIsFraudDetected(true);
                } else {
                    $payment->setState('pending');
                }

                $session->setIs3d(false);
            } else {
                
                $errorMessage = Mage::helper('chargepayment')->__('An error has occured, please verify your payment details and try again.');

                Mage::log($result->responseCode, null, $this->_code.'.log');
                Mage::throwException($errorMessage);
            }                      

            return $this;
        }
    }

    /**
     * Return base data for charge
     *
     * @param null $amount
     * @param null $quoteId
     * @return array
     *
     * @version 20160204
     */
    private function _getCharge($amount = null, $quoteId = null) {
        $secretKey          = $this->_getSecretKey();
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $quote              = $this->_getQuote($quoteId);

        $billingAddress     = $quote->getBillingAddress();
        $shippingAddress    = $quote->getShippingAddress();
        $orderedItems       = $quote->getAllItems();
        $currencyDesc       = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();
        $amountCents        = $amount;
        $shippingCost       = $quote->getShippingAddress()->getShippingAmount();

        $street = Mage::helper('customer/address')
            ->convertStreetLines($shippingAddress->getStreet(), 2);

        $billingAddressConfig = array (
            'addressLine1'  => $street[0],
            'addressLine2'  => $street[1],
            'postcode'      => $billingAddress->getPostcode(),
            'country'       => $billingAddress->getCountry(),
            'city'          => $billingAddress->getCity(),
            'state'         => $billingAddress->getRegion(),
            'phone'         => array('number' => $billingAddress->getTelephone())
        );

        $billingPhoneNumber = $billingAddress->getTelephone();

        if (!empty($billingPhoneNumber)) {
            $billingAddressConfig['phone'] = array('number' => $billingPhoneNumber);
        }

        $shippingAddressConfig = array(
            'recipientName'      => $shippingAddress->getName(),
            'addressLine1'       => $street[0],
            'addressLine2'       => $street[1],
            'postcode'           => $shippingAddress->getPostcode(),
            'country'            => $shippingAddress->getCountry(),
            'city'               => $shippingAddress->getCity(),
            'state'              => $shippingAddress->getCity()
        );

        $phoneNumber = $shippingAddress->getTelephone();

        if (!empty($phoneNumber)) {
            $shippingAddressConfig['phone'] = array('number' => $phoneNumber);
        }

        $products = array();

        foreach ($orderedItems as $item) {
            $product        = Mage::getModel('catalog/product')->load($item->getProductId());
            $productPrice   = $item->getPrice();
            $productPrice   = is_null($productPrice) || empty($productPrice) ? 0 : $productPrice;
            $productImage   = $product->getImage();

            $products[] = array (
                'name'       => $item->getName(),
                'sku'        => $item->getSku(),
                'price'      => $productPrice,
                'quantity'   => $item->getQty(),
                'image'      => $productImage != 'no_selection' && !is_null($productImage) ? Mage::helper('catalog/image')->init($product , 'image')->__toString() : '',
                'shippingCost' => $shippingCost
            );
        }

        $config                     = array();
        $config['authorization']    = $secretKey;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $config['postedParam'] = array (
            'trackId'           => NULL,
            'customerName'      => $billingAddress->getName(),
            'email'             => Mage::helper('chargepayment')->getCustomerEmail($quoteId),
            'value'             => $amountCents,
            'currency'          => $currencyDesc,
            'billingDetails'    => $billingAddressConfig,
            'shippingDetails'   => $shippingAddressConfig,
            'products'          => $products,
            'customerIp'        => $ip,
            'metadata'          => array(
                'server'            => Mage::helper('core/http')->getHttpUserAgent(),
                'quoteId'           => $quote->getId(),
                'magento_version'   => Mage::getVersion(),
                'plugin_version'    => Mage::helper('chargepayment')->getExtensionVersion(),
                'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                'integration_type'  => 'FramesJs',
                'time'              => Mage::getModel('core/date')->date('Y-m-d H:i:s')
            ),

        );

        $autoCapture = 'n';

        if ($this->getAutoCapture() ==1){
            $autoCapture = 'y';
        }

        $config['postedParam']['autoCapture']  = $autoCapture;
        $config['postedParam']['autoCapTime']  = $this->getAutoCapTime();

        return $config;
    }

    /*
    * Get Auto capture time from module settings
    */
    public function getAutoCapTime(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'autoCapTime');
    }

    /*
    * Get Payment action from module settings
    */
    public function getAutoCapture(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'autoCapture');
    }

    /*
    * Get Public key from module settings
    */
    public function getPublicKey() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey');
    }

    /*
    * Get Google pay merchant id from module settings
    */
    public function getGooglePayMerchantId(){

        return Mage::helper('chargepayment')->getConfigData($this->_code, 'gPayMerchantId');
    }

    /*
    * Create google pay charge
    */
    public function createGoogleCharge($requestData, $payment){
        $signature = $requestData["cko-google-signature"];
        $protocolVersion = $requestData["cko-google-protocolVersion"];
        $signedMessage = $requestData["cko-google-signedMessage"];
        $publicKey = Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey');
        $endPointMode = $this->getEndpointMode();

        $createTokenUrl = "https://sandbox.checkout.com/api2/tokens";

        if($endPointMode == 'live'){
            $createTokenUrl = "https://api2.checkout.com/tokens";
        }

        $token_data = array(
            'signature' => $signature,
            'protocolVersion' => $protocolVersion,
            'signedMessage' => $signedMessage,
        );

        //  GET TOKEN
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $createTokenUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: '.$publicKey,
            'Content-Type:application/json;charset=UTF-8'
            ));
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            json_encode( array(
                'type' => 'googlepay',
                'token_data' => $token_data,
            )));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $server_output = curl_exec($ch);
        curl_close ($ch);

        $response = json_decode($server_output);
        $GoogleToken = $response->token;

        if(!empty($GoogleToken)){
            $orderId = $payment->getOrder()->getIncrementId();
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $Api = CheckoutApi_Api::getApi(array('mode' => $this->getEndpointMode()));
            $amount     = $Api->valueToDecimal($price, $priceCode);
            $currencyCode =  Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->getSymbol();
            $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
            $priceCode          = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();
            $quoteId = $order->getQuoteId();
            $grandTotal = $order->getGrandTotal();
            $value     = $Api->valueToDecimal($grandTotal, $priceCode);

            $config = $this->_getCharge($grandTotal, $quoteId);
            $config['postedParam']['trackId'] = $orderId;
            $config['postedParam']['transactionIndicator'] = '2';
            $config['postedParam']['cardToken'] = $GoogleToken;
            $config['postedParam']['value'] = $value; 

            $createChargeUrl = "https://sandbox.checkout.com/api2/v2/charges/token";

            if($endPointMode == 'live'){
                $createChargeUrl = "https://api2.checkout.com/v2/charges/token";
            }

             //  CHARGE REQUEST
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$createChargeUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: '.$this->_getSecretKey(),
                'Content-Type:application/json;charset=UTF-8'
                ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config['postedParam']));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $server_output = curl_exec($ch);
            curl_close ($ch);

            $response = json_decode($server_output);

            return $response;

        } else {

            $errorMessage = 'An error has occured, please verify your payment details and try again.';
            Mage::log('Empty GoogleToken', null, $this->_code.'.log');
            Mage::throwException($errorMessage);
        }
    }

    /**
    * Get Payment information to send to Google
    **/
    public function getPaymentInfo() {
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $price              = $isCurrentCurrency ? $this->_getQuote()->getGrandTotal() : $this->_getQuote()->getBaseGrandTotal();
        $priceCode          = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();
        $environment = $this->getEndpointMode() == 'live' ? 'PRODUCTION' : 'TEST';

        $config     = $this->_getCharge($amount);

        $googlePayInfo = array();
        $googlePayInfo['value']     = $price;
        $googlePayInfo['currency']  = $priceCode;
        $googlePayInfo['customerName'] = $config['postedParam']['customerName'];
        $googlePayInfo['environment'] = $environment;

        return $googlePayInfo;
    }
}