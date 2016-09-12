<?php
/**
 * Class CheckoutApi_ChargePayment_Helper_Data
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Helper_Data  extends Mage_Core_Helper_Abstract
{
    const CODE_CREDIT_CARD                  = 'checkoutapicard';
    const CODE_CREDIT_CARD_HOSTED           = 'checkoutapihosted';
    const CODE_CREDIT_CARD_KIT              = 'checkoutapikit';

    const JS_PATH_CARD_TOKEN                = 'https://cdn.checkout.com/sandbox/js/checkout.js';
    const JS_PATH_CARD_TOKEN_LIVE           = 'https://cdn.checkout.com/js/checkout.js';
    const JS_PATH_CHECKOUT_KIT_LIVE         = 'https://cdn.checkout.com/js/checkoutkit.js';
    const JS_PATH_CHECKOUT_KIT              = 'https://sandbox.checkout.com/js/checkoutkit.js';
    const REDIRECT_PAYMENT_URL              = 'https://secure.checkout.com/sandbox/payment/';
    const REDIRECT_PAYMENT_URL_LIVE         = 'https://secure.checkout.com/payment/';

    const CREDIT_CARD_CHARGE_MODE_NOT_3D    = 1;
    const CREDIT_CARD_CHARGE_MODE_3D        = 2;
    const PAYMENT_ACTION_AUTHORIZE_CAPTURE  = 'authorize_capture';
    const API_MODE_LIVE                     = 'live';
    const API_MODE_SANDBOX                  = 'sandbox';

    /**
     * Return field from config by payment method and store ID
     *
     * @param $method
     * @param $field
     * @param null $storeId
     * @return mixed
     *
     * @version 20151006
     */
    public function getConfigData($method, $field, $storeId = NULL) {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore();
        }

        $path = "payment/{$method}/" . $field;

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Return js Path for Hosted method
     *
     * @return string
     */
    public function getJsPath() {
        $mode   = (string)$this->getConfigData(self::CODE_CREDIT_CARD_HOSTED, 'mode');
        $jsUrl  = $mode === self::API_MODE_LIVE ? self::JS_PATH_CARD_TOKEN_LIVE : self::JS_PATH_CARD_TOKEN;

        return $jsUrl;
    }

    /**
     * Return js Path for Checkout Kit
     *
     * @return string
     *
     * @version 20160502
     */
    public function getKitJsPath() {
        $mode   = (string)$this->getConfigData(self::CODE_CREDIT_CARD_KIT, 'mode');
        $jsUrl  = $mode === self::API_MODE_LIVE ? self::JS_PATH_CHECKOUT_KIT_LIVE : self::JS_PATH_CHECKOUT_KIT;

        return $jsUrl;
    }

    /**
     * Return current extension version
     *
     * @return string
     *
     * @version 20160510
     */
    public function getExtensionVersion() {
        return (string)Mage::getConfig()->getModuleConfig("CheckoutApi_ChargePayment")->version;
    }

    /**
     * Return Customer Email
     *
     * @return string
     */
    public function getCustomerEmail() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $quote->getBillingAddress()->getEmail();

        if (!empty($email)) {
            return $email;
        }

        $isLogged = Mage::getSingleton('customer/session')->isLoggedIn();

        if (!$isLogged) {
            return '';
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();

        return $customer->getEmail();
    }

    /**
     * Restore stock items qty for restored session
     *
     * @param Mage_Checkout_Model_Cart $cart
     * @return bool
     */
    public function restoreStockItemsQty(Mage_Checkout_Model_Cart $cart) {
        foreach($cart->getItems() as $item) {
            if ($item->getHasChildren()) {
                continue;
            }

            $quantity       = $item->getQty();
            $productSku     = $item->getSku();
            $product        = Mage::getModel('catalog/product')->loadByAttribute('sku', $productSku);

            if($product) {
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $stock->setQty($stock->getQty() + $quantity);
                $stock->save();
            }
        }

        return true;
    }
}
