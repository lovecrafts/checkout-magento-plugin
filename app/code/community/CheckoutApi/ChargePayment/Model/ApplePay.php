<?php

/**
 * Class for ApplePay payment method
 *
 * Class CheckoutApi_ChargePayment_Model_ApplePay
 *
 */
class CheckoutApi_ChargePayment_Model_ApplePay extends CheckoutApi_ChargePayment_Model_Checkout
{
    protected $_code            = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_APPLE_PAY;
    protected $_canUseInternal  = false;

    protected $_formBlockType = 'chargepayment/form_checkoutApiApplePay';
    protected $_infoBlockType = 'chargepayment/info_checkoutApiApplePay';


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
        if (empty($amount)) { 
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
    * Get apple pay merchant identifier from module settings
    */
    public function getApplePayMerchantIdentifier(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'appleMerchantIdentifier');
    }

    /*
    * Get Google pay merchant certificate.pem path from module settings
    */
    public function getApplePayCertPath(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'appleCertPath');
    }

    /*
    * Get Google pay merchant certificate.key path from module settings
    */
    public function getApplePayCertKey(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'appleCertKey');
    }

    /**
    * Get Payment information
    **/
    public function getPaymentInfo() {
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $price              = $isCurrentCurrency ? $this->_getQuote()->getGrandTotal() : $this->_getQuote()->getBaseGrandTotal();
        $priceCode          = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();
        $environment = $this->getEndpointMode() == 'live' ? 'PRODUCTION' : 'TEST';

        $Api        = CheckoutApi_Api::getApi(array('mode' => $this->getEndpointMode()));
        $amount     = $Api->valueToDecimal($price, $priceCode);
        $config     = $this->_getCharge($amount);

        $applePayInfo = array();
        
        $applePayInfo['currency']  = $priceCode;
        $applePayInfo['customerName'] = $config['postedParam']['customerName'];
        $applePayInfo['environment'] = $environment;
        $applePayInfo['countryCode'] = $config['postedParam']['billingDetails']['country'];
        $applePayInfo['products'] = $config['postedParam']['products'];

        if(empty($applePayInfo['products'])){
            if(empty($postedParam['products'])){
                $current_product = Mage::registry('current_product');
                if($current_product) {
                    $sku = $current_product->getSku();
                    $products[] = array (
                        'name'       => $current_product->getName(),
                        'sku'        => $current_product->getSku(),
                        'price'      => (float) $current_product->getPrice(),
                        'quantity'   => 1,
                    );
                }

                $config['postedParam']['products'] = $products;
                $applePayInfo['products'] = $products;
            }
        }

        $carriers = Mage::getStoreConfig('carriers', Mage::app()->getStore()->getId());
        foreach ($carriers as $carrierCode => $carrierConfig) {
            if($carrierConfig['active']){
                $applePayInfo['shippingMethod'][] = $carrierConfig;
            }
        }

        if(Mage::registry('current_product')) {
            $product = Mage::registry('current_product');
            $id = $product->getId();
            $qty = $product->getStockItem()->getQty();
            $productPrice = round($product->getPrice(),2);
            $applePayInfo['subtotal'] = $productPrice;
            $applePayInfo['value']     = $productPrice;
        } else {
            $applePayInfo['value']     = $price;
            $applePayInfo['selectedShippingMethod'] = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingDescription();
            $applePayInfo['selectedShippingMethodCode'] = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod();
            $applePayInfo['selectedShippingAmount'] = round(Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingAmount(), 2);
            $applePayInfo['subtotal'] = round($price - $applePayInfo['selectedShippingAmount'], 2);
        }

        $applePayInfo['storeName'] = Mage::app()->getStore()->getName();
        $result = array_merge($applePayInfo, $config);
        
        return $result;
    }
}