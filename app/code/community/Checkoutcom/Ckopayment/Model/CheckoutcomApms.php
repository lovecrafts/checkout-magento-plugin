<?php

use Checkout\CheckoutApi;
use Checkout\Models\Address;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\Shipping;
use Checkout\Models\Phone;
use Checkout\Models\Product;
use Checkout\Models\Payments\SofortSource;
use Checkout\Models\Payments\AlipaySource;
use Checkout\Models\Payments\BoletoSource;
use Checkout\Models\Payments\IdealSource;
use Checkout\Models\Payments\GiropaySource;
use Checkout\Models\Payments\PoliSource;
use Checkout\Models\Payments\KlarnaSource;
use Checkout\Models\Sources\Sepa;
use Checkout\Models\Sources\SepaData;
use Checkout\Models\Sources\BillingAddress;
use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Library\Exceptions\CheckoutModelException;
use Checkout\Models\Payments\EpsSource;
use Checkout\Models\Payments\BancontactSource;
use Checkout\Models\Payments\KnetSource;
use Checkout\Models\Payments\FawrySource;


/**
 * Class Checkoutcom_Ckopayment_Model_CheckoutcomApms
 */
class Checkoutcom_Ckopayment_Model_CheckoutcomApms extends Mage_Payment_Model_Method_Cc
{
    protected $_code  = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_APMS;
    protected $_formBlockType = 'ckopayment/form_checkoutcomApms';
    protected $_infoBlockType = 'ckopayment/info_checkoutcomApms';

    protected $_isGateway       = true;
    protected $_canUseInternal  = false;
    protected $_canUseCheckout  = true;
    protected $_canAuthorize    = true;
    protected $_canCapture      = false;
    protected $_canRefund       = false;
    protected $_canVoid         = false;
    protected $_canOrder        = true;
    protected $_canSaveCc       = false;
    protected $_canRefundInvoicePartial = false;

    /**
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        return $this;
    }

    /**
     * Used when there is redirection.
     * Example 3Ds and APMs
     * @return bool
     */
    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('ckopayment/session_quote');
        $isApm = $session->getIsApm();
        $isApmUrl = $session->getPaymentRedirectUrl();

        $session
            ->setIsApm(false)
            ->setPaymentRedirectUrl(false);

        if ($isApm && $isApmUrl) {
            return $isApmUrl;
        }

        return false;
    }

    /**
     * Used to process auth payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $requestData = Mage::app()->getRequest()->getParam('payment');
        $session = Mage::getSingleton('ckopayment/session_quote');

        // Order information
        $order = $payment->getOrder();
        $quoteId = $order->getQuoteId();
        $orderId = $payment->getOrder()->getIncrementId();

        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        // Format amount to cent
        $amountCent = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($amount, $currencyCode);

        // Charge request params
        $requestParam = $this->_getRequestParam($requestData, $amountCent, $currencyCode, $quoteId, $orderId);

        $secretKey = Mage::getModel('ckopayment/checkoutcomConfig')->getSecretKey();

        $environment =  Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        // Initialize the Checkout Api
        $checkout = new CheckoutApi($secretKey, $environment);

        try {

            // Call to create charge
            $response = $checkout->payments()->request($requestParam);

            // Check if payment successful
            if ($response->isSuccessful()) {
                if ($response->isPending() || $response->status == 'Authorized') {
                    if ($response->getRedirection()) {
                        // Update order payment information with payment id from checkout.com
                        $payment->setAdditionalInformation('isApm', true);
                        $payment->setIsTransactionPending(true);

                        $session
                            ->setIsApm(true)
                            ->setPaymentRedirectUrl($response->getRedirection());
                    } else {

                        // Verify payment id
                        $verifyPayment = $checkout->payments()->details($response->id);
                        $source = $verifyPayment->source;

                        // Check if payment is successful
                        if ($verifyPayment->isSuccessful()) {
                            $metadata = $verifyPayment->metadata;

                            // Check if payment source is sepa
                            if ($source['type'] == 'sepa') {
                                $payment->setTransactionId($response->id);
                                $payment->setIsTransactionClosed(0);

                                // Set message to order with mandate id
                                $order->addStatusHistoryComment("Sepa payment completed. Mandate Id : " .$metadata['mandate_id']);
                                $order->save();

                                return $this;
                            }
                        }

                        // Check if payment source if Fawry
                        if ($source['type'] == 'fawry'){
                            $payment->setTransactionId($response->id);
                            $payment->setIsTransactionClosed(0);

                            // Set message to order with mandate id
                            $order->addStatusHistoryComment("Fawry payment completed.");
                            $order->save();

                            return $this;
                        }

                        $errorMessage = "Redirection url not found.";

                        // Log error in var/log/checkoutcomapms.log
                        Mage::log($errorMessage, null, $this->_code . '.log');
                        Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

                        //Throw exception and stop order process
                        Mage::throwException($errorMessage);
                    }
                } else {
                    $errorMessage = "An error has occurred while processing your payment. Please check your payment details and try again.";

                    // Log error in var/log/checkoutcomapms.log
                    Mage::log($errorMessage, null, $this->_code . '.log');
                    Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

                    //Throw exception and stop order process
                    Mage::throwException($errorMessage);
                }
            } else {
                $errorMessage = "An error has occurred while processing your payment. Please check your payment details and try again.";

                // Log error in var/log/checkoutcomapms.log
                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

                //Throw exception and stop order process
                Mage::throwException($errorMessage);
            }
        } catch (CheckoutModelException $ex) {
            Mage::log($ex->getBody(), Zend_Log::DEBUG, $this->_code . '.log', true);
            $errorMessage = "An error has occurred while processing your payment. Please check your payment details and try again.";
            Mage::throwException($errorMessage);
        } catch (CheckoutHttpException $ex) {
            Mage::log($ex->getBody(), Zend_Log::DEBUG, $this->_code . '.log', true);
            $errorMessage = "An error has occurred . Please check your payment details and try again.";
            Mage::throwException($errorMessage);
        }

        return $this;
    }

    /**
     * Return full charge request
     *
     * @param $requestData
     * @param $amount
     * @param $currencyCode
     * @param $quoteId
     * @param $orderId
     * @return Payment
     * @throws Mage_Core_Model_Store_Exception
     */
    private function _getRequestParam($requestData, $amount, $currencyCode, $quoteId, $orderId)
    {
        $quote = Mage::getModel('ckopayment/checkoutcomUtils')->getQuote($quoteId);
        $apmName = $requestData['cko_apms'];

        $secretKey = Mage::getModel('ckopayment/checkoutcomConfig')->getSecretKey();
        $environment =  Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        // Initialize the Checkout Api
        $checkout = new CheckoutApi($secretKey, $environment);

        // Get billing address from quote
        $billingAddress = $quote->getBillingAddress();
        // Get billing Street from billing address
        $billStreet = Mage::helper('customer/address')
            ->convertStreetLines($billingAddress->getStreet(), 2);

        // Get Shipping address from quote
        $shippingAddress = $quote->getShippingAddress();
        // Get Shipping Street from billing address
        $shipStreet = Mage::helper('customer/address')
            ->convertStreetLines($shippingAddress->getStreet(), 2);

        // Get phone number from shipping
        $phoneNumber = $shippingAddress->getTelephone();

        $autoCapture = Mage::getModel('ckopayment/checkoutcomConfig')->getPaymentAction() == 1 ? true : false;

        // Set Billing address in param
        $billingAddressParam = new Address();
        $billingAddressParam->address_line1 = $billStreet[0];
        $billingAddressParam->address_line2 = $billStreet[1];
        $billingAddressParam->city = $billingAddress->getCity();
        $billingAddressParam->state = $billingAddress->getRegion();
        $billingAddressParam->zip = $billingAddress->getPostcode();
        $billingAddressParam->country = $billingAddress->getCountry();

        // Create a payment method instance depending on apm name
        switch ($apmName) {
            case 'alipay':
                $method = new AlipaySource();
                break;
            case 'boleto':
                $customerName = $requestData['cko-cust-name'];
                $birthData = $requestData['cko-boleto-date'];
                $cpf = $requestData['cko-cpf'];
                $method = new BoletoSource($customerName, $birthData, $cpf);

                break;
            case 'giropay':
                $bic = $requestData['cko-giropay-bank'];
                $purpose = "#{$orderId}-{$_SERVER['HTTP_HOST']}";
                $method = new GiropaySource($purpose, $bic);
                break;
            case 'ideal':
                $bic = $requestData['cko-ideal-bic'];
                $description = $orderId;
                $method = new IdealSource($bic, $description);
                break;
            case 'klarna':
                $klarnaToken = $requestData['cko-klarna-token'];
                $countryCode = $billingAddress->getCountry();
                $localisation = Mage::app()->getLocale()->getLocaleCode();
                $locale = 'en-GB' ;//str_replace("_","-",$localisation);

                $items =Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
                $products = array();

                foreach($items as $item) {
                    $unitPrice = Mage::getModel('ckopayment/checkoutcomutils')
                        ->valueToDecimal($item->getPrice(), $currencyCode);

                    $products[] = array(
                        "name" => $item->getName(),
                        "quantity" => $item->getQty(),
                        "unit_price" => $unitPrice,
                        "tax_rate" => 0,
                        "total_amount" => $unitPrice * $item->getQty(),
                        "total_tax_amount" => 0,
                        "type" => "physical",
                        "reference" => $item->getName(),
                        "total_discount_amount" => 0

                    );
                }

                $shippingMethod = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getShippingAddress()
                    ->getShippingDescription();
                $shippingMethodCode = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getShippingAddress()
                    ->getShippingMethod();
                $shippingAmount = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getShippingAddress()
                    ->getShippingAmount();

                $shippingAmountCents = Mage::getModel('ckopayment/checkoutcomutils')
                    ->valueToDecimal($shippingAmount, $currencyCode);
                // Set shipping method as product to calculate klarna total amount correctly.
                $products[] = array(
                    "name" => $shippingMethod,
                    "quantity" => 1,
                    "unit_price" => $shippingAmountCents,
                    "tax_rate" => 0,
                    "total_amount" => $shippingAmountCents,
                    "total_tax_amount" => 0,
                    "type" => "shipping_fee",
                    "reference" => $shippingMethodCode,
                    "total_discount_amount" => 0
                );

                $billingAddressParam = new Address();
                $billingAddressParam->given_name = $billingAddress->getFirstname();
                $billingAddressParam->family_name = $billingAddress->getLastname();
                $billingAddressParam->email = Mage::helper('ckopayment')->getCustomerEmail(null);
                $billingAddressParam->street_address = $billStreet[0];
                $billingAddressParam->street_address2 = $billStreet[1];
                $billingAddressParam->postal_code = $billingAddress->getPostcode();
                $billingAddressParam->city = $billingAddress->getCity();
                $billingAddressParam->region = $billingAddress->getCity();
                $billingAddressParam->phone = $phoneNumber;
                $billingAddressParam->country = $billingAddress->getCountry();

                $method = new KlarnaSource($klarnaToken, $countryCode, $locale, $billingAddressParam, 0, $products);

                break;
            case 'poli':
                $method = new PoliSource();
                break;
            case 'sepa':
                $bic = $requestData['cko-sepa-bic'];
                $iban = $requestData['cko-sepa-iban'];

                $first_name = $billingAddress->getFirstname();
                $last_name = $billingAddress->getLastname();
                $account_iban = $iban;
                $bic = $bic;
                $billing_descriptor = "Thanks";
                $mandate_type = 'single';

                $address_line1 = $billStreet[0];
                $address_line2 = $billStreet[1];
                $city = $billingAddress->getCity();
                $state = $billingAddress->getRegion();
                $zip = $billingAddress->getPostcode();
                $country = $billingAddress->getCountry();

                // Set Billing address in sepa
                $sepaBillingAddress = new BillingAddress($address_line1, $address_line2, $city, $state, $zip, $country );
                // Set Sepa Data
                $sepaDataParam = new SepaData($first_name, $last_name, $account_iban, $bic, $billing_descriptor, $mandate_type );

                $sepaData = new Sepa($sepaBillingAddress, $sepaDataParam);

                $sepaData->customer = array(
                    'email' => Mage::helper('ckopayment')->getCustomerEmail($quoteId),
                );

                $sepaData->reference = $orderId;

                // Sepa source request
                $sepa = $checkout->sources()->add($sepaData);

                $method = new Checkout\Models\Payments\IdSource($sepa->getId());
                break;
            case 'sofort':
                $method = new SofortSource();
                break;
            case 'eps':
                $store = Mage::app()->getStore();
                $purpose = $store->getName();

                $method =  new EpsSource($purpose);
                break;
            case 'bancontact':
                $accountHolder = $billingAddress->getFirstname() . ' '. $billingAddress->getLastname();
                $countryCode = $country = $billingAddress->getCountry();

                $method = new BancontactSource($accountHolder, $countryCode);
                break;
            case 'knet':
                $language = Mage::getStoreConfig('general/locale/code');

                switch ($language) {
                    case 'ar_SA':
                        $language = 'ar';
                        break;
                    default:
                        $language = 'en';
                        break;
                }

                $method = new KnetSource($language);
                break;
            case 'fawry':

                $email = Mage::helper('ckopayment')->getCustomerEmail($quoteId);

                $items =Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
                $products = array();

                foreach($items as $item) {
                    $unitPrice = Mage::getModel('ckopayment/checkoutcomutils')
                        ->valueToDecimal($item->getPrice(), $currencyCode);

                    $products[] = array(
                        "product_id" => $item->getId(),
                        "quantity" => $item->getQty(),
                        "price" => $unitPrice,
                        "description" => $item->getName(),
                    );
                }

                $shippingMethod = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getShippingAddress()
                    ->getShippingDescription();
                $shippingMethodCode = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getShippingAddress()
                    ->getShippingMethod();
                $shippingAmount = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getShippingAddress()
                    ->getShippingAmount();

                $shippingAmountCents = Mage::getModel('ckopayment/checkoutcomutils')
                    ->valueToDecimal($shippingAmount, $currencyCode);
                
                // If shipping method is not free shipping
                // Set shipping method as product to calculate fawry total amount correctly.
                if ($shippingMethodCode !== 'freeshipping_freeshipping') {
                    // Set shipping method as product to calculate klarna total amount correctly.
                    $products[] = array(
                        "product_id" => $shippingMethodCode,
                        "quantity" => 1,
                        "price" => $shippingAmountCents,
                        "description" => $shippingMethod,
                    );
                }

                $method = new FawrySource($email, $phoneNumber, $orderId, $products);
                break;
            default:
                $method = new SofortSource();
                break;
        }

        $method->billing_address = $billingAddressParam;

        // Prepare the payment parameters
        $payment = new Payment($method, $currencyCode);
        $payment->capture = false;//$autoCapture;
        $payment->amount = $amount;
        $payment->reference = $orderId;

        $payment->customer = array(
            'email' => Mage::helper('ckopayment')->getCustomerEmail($quoteId),
            'name' => $billingAddress->getName(),
        );

        // Set shipping Address
        $shippingAddressParam = new Address();
        $shippingAddressParam->address_line1 = $shipStreet[0];
        $shippingAddressParam->address_line2 = $shipStreet[1];
        $shippingAddressParam->city = $shippingAddress->getCity();
        $shippingAddressParam->state = $shippingAddress->getRegion();
        $shippingAddressParam->zip = $shippingAddress->getPostcode();
        $shippingAddressParam->country = $shippingAddress->getCountry();

        $phone = new Phone();
        $phone->number = $phoneNumber;

        $payment->shipping = new Shipping($shippingAddressParam, $phone);

        // Set redirection url in payment request
        $payment->success_url = Mage::getBaseUrl() . 'ckopayment/api/success';
        $payment->failure_url = Mage::getBaseUrl() . 'ckopayment/api/error';

        // Set additional info in payment request
        $metadata = array(
            'server' => Mage::helper('core/http')->getHttpUserAgent(),
            'quoteId' => $quote->getId(),
            'magento_version' => Mage::getVersion(),
            'plugin_version' => Mage::helper('ckopayment')->getExtensionVersion(),
            'lib_version' => CheckoutApi::VERSION,
            'integration_type' => $apmName,
            'time' => Mage::getModel('core/date')->date('Y-m-d H:i:s'),
            'udf5' => 'Magento - '. Mage::getVersion()
                . ', Checkout Plugin - ' . Mage::helper('ckopayment')->getExtensionVersion()
                . ', Php Sdk - '. CheckoutApi::VERSION
        );

        if($apmName == 'sepa'){
            $sepaResult = $sepa->response_data;
            $metadata = array_merge($metadata, array('mandate_id' => $sepaResult['mandate_reference']));
        }

        $payment->metadata = $metadata;

        return $payment;
    }
}
