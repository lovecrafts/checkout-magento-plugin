<?php

use Checkout\CheckoutApi;
use Checkout\Models\Address;
use Checkout\Models\Payments\Capture;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\Refund;
use Checkout\Models\Payments\Shipping;
use Checkout\Models\Payments\TokenSource;
use Checkout\Models\Payments\Voids;
use Checkout\Models\Phone;
use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Library\Exceptions\CheckoutModelException;

/**
 * Class Checkoutcom_Ckopayment_Model_CheckoutcomApplePay
 */
class Checkoutcom_Ckopayment_Model_CheckoutcomApplePay extends Mage_Payment_Model_Method_Cc
{
    protected $_code = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_APPLEPAY;
    protected $_formBlockType = 'ckopayment/form_checkoutcomApplePay';
    protected $_infoBlockType = 'ckopayment/info_checkoutcomApplePay';

    protected $_isGateway = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canOrder = true;
    protected $_canSaveCc = false;
    protected $_canRefundInvoicePartial = true;

    /**
     * Return secret key from checkout.com config model
     *
     * @return mixed
     */
    private function _getSecretKey()
    {
        return Mage::getModel('ckopayment/checkoutcomConfig')->getSecretKey();
    }

    /**
     * Get Auto capture from admin module setting
     *
     * @return mixed
     */
    private function _getAutoCapture()
    {
        return Mage::getModel('ckopayment/checkoutcomConfig')->getPaymentAction();
    }

    /**
     * Return full charge request
     *
     * @param $cardToken
     * @param $amount
     * @param $currencyCode
     * @param $quoteId
     * @return Payment
     */
    private function _getRequestParam($cardToken, $amount, $currencyCode, $quoteId, $orderId)
    {
        $quote = Mage::getModel('ckopayment/checkoutcomUtils')->getQuote($quoteId);

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

        $autoCapture = $this->_getAutoCapture() == 1 ? true : false;

        // Create a payment method instance with card token
        $method = new TokenSource($cardToken);

        // Set Billing address in param
        $billingAddressParam = new Address();
        $billingAddressParam->address_line1 = $billStreet[0];
        $billingAddressParam->address_line2 = $billStreet[1];
        $billingAddressParam->city = $billingAddress->getCity();
        $billingAddressParam->state = $billingAddress->getRegion();
        $billingAddressParam->zip = $billingAddress->getPostcode();
        $billingAddressParam->country = $billingAddress->getCountry();
        $method->billing_address = $billingAddressParam;

        // Prepare the payment parameters
        $payment = new Payment($method, $currencyCode);
        $payment->capture = $autoCapture;
        $payment->amount = $amount;
        $payment->reference = $orderId;

        // set capture delay if payment action is authorise and capture
        if($autoCapture){
            $captureDelay =  Mage::getModel('ckopayment/checkoutcomConfig')->getDelayedCaptureTimestamp();
            $payment->capture_on = $captureDelay;
        }

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
        $payment->metadata = array(
            'server' => Mage::getBaseUrl(),
            'sdk_data' => "PHP SDK v".CheckoutApi::VERSION,
            'integration_data' => "Checkout.com Magento Plugin v".Mage::helper('ckopayment')->getExtensionVersion(),
            'platform_data' => "Magento v".Mage::getVersion(),
            'quoteId' => $quote->getId(),
        );

        return $payment;
    }

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
        return false;
    }

    /**
     * Return applepay button language from admin module config
     * @return mixed
     */
    public function validateSession(string $url, string $certificate, string $certificateKey)
    {
        return $url . " " . $certificate . " " . $certificateKey;
    }

    /**
     * Return payment info
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getPaymentInfo()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $total = $quote->getGrandTotal();
        $subTotal = $quote->getSubtotal();
        $discountTotal = 0;
        foreach ($quote->getAllItems() as $item) {
            $discountTotal += $item->getDiscountAmount();
        }
        $shippingCost = $quote->getShippingAddress()->getShippingAmount();
        $shippingMethod = $quote->getShippingAddress()->getShippingDescription();
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        $arr = array(
            'total' => $total,
            'subTotal' => $subTotal,
            'shippingCost' => $shippingCost,
            'shippingMethod' => $shippingMethod,
            'currency' => $currencyCode,
            'discounts' => $discountTotal,
        );

        return $arr;
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

        // Check if card token exist
        if (empty($requestData['apple_cko_card_token'])) {
            $message = "Card token empty";

            // Log error in var/log/checkoutcomframes.log
            Mage::log($message, null, $this->_code . '.log');

            //Throw exception and stop order process
            Mage::throwException($message);
        }

        // Order information
        $order = $payment->getOrder();
        $quoteId = $order->getQuoteId();
        $orderId = $payment->getOrder()->getIncrementId();

        // card token
        $cardToken = $requestData['apple_cko_card_token'];
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        // Format amount to cent
        $amountCent = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($amount, $currencyCode);

        // Charge request params
        $requestParam = $this->_getRequestParam($cardToken, $amountCent, $currencyCode, $quoteId, $orderId);

        $environment =  Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        // Initialize the Checkout Api
        $checkout = new CheckoutApi($this->_getSecretKey(), $environment);

        // Call to create charge
        $response = $checkout->payments()->request($requestParam);

        // Check if payment successful
        if ($response->isSuccessful()) {
            // Check if payment is 3Dsecure
            if ($response->isPending()) {
                // Check if redirection link exist
                if ($response->getRedirection()) {
                    // do redirection to 3D page
                    // Update order payment information with payment id from checkout.com
                    $payment->setTransactionId($response->id);
                    $payment->setAdditionalInformation('is3d', true);
                    $payment->setIsTransactionPending(true);
                    $payment->setIsTransactionClosed(0);

                    $session
                        ->setIs3d(true)
                        ->setPaymentRedirectUrl($response->getRedirection());
                } else {
                    $errorMessage = "An error has occurred while processing your payment. Redirection link not found";

                    // Log error in var/log/checkoutcomframes.log
                    Mage::log($errorMessage, null, $this->_code . '.log');

                    //Throw exception and stop order process
                    Mage::throwException($errorMessage);
                }
            } else {
                // Payment successful continue order process

                // check if payment was flagged
                if ($response->risk['flagged']) {
                    // Payment Flagged status from config
                    $flagStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getFlaggedOrderStatus();

                    if ($flagStatus == "suspected_fraud") {
                        $payment->setIsFraudDetected(true);
                    }

                    $order->addStatusHistoryComment('Payment flagged on Checkout.com ', false);
                }

                $source = $response->source;

                switch ($source['type']) {
                    case 'card':
                        $order->getPayment()->setCcLast4($source['last4'])->setCcType($source['scheme']);
                        break;
                    default:
                        break;
                }

                // Update order payment information with payment id from checkout.com
                $payment->setTransactionId($response->id);
                $payment->setAdditionalInformation('ckoPaymentId', $response->id);
                $payment->setIsTransactionClosed(0);
            }
        } else {
            $errorMessage = "An error has occurred while processing your payment. Please check your card details and try again.";

            // Log error in var/log/checkoutcomframes.log
            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

            //Throw exception and stop order process
            Mage::throwException($errorMessage);
        }

        return $this;
    }

    /**
     * Used to process capture from backend
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $ckoPaymentId = $payment->getAdditionalInformation('ckoPaymentId');
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        // Check if CKO payment id exist in order
        if (empty($ckoPaymentId)) {
            $errorMessage = 'CKO PaymentId not found for order Id : ' . $orderId;

            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::throwException($errorMessage);
        }

        $currencyCode = $order->getOrderCurrencyCode();
        $grandTotals = $order->getGrandTotal();
        $grandTotalsCents = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($grandTotals, $currencyCode);
        $amountCents = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($amount, $currencyCode);

        $amountLessThanGrandTotal = $amountCents < $grandTotalsCents ? true : false;

        $environment =  Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        // Initialize the Checkout Api
        $checkout = new CheckoutApi($this->_getSecretKey(), $environment);

        try {
            // Check if payment is already voided or captured on checkout.com hub
            $details = $checkout->payments()->details($ckoPaymentId);

            if ($details->status == 'Voided' || $details->status == 'Captured' && !$amountLessThanGrandTotal) {
                $errorMessage = 'Payment has already been voided or captured on Checkout.com hub for order Id : ' . $orderId;

                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::throwException($errorMessage);

                return $this;
            }

            $ckoPayment = new Capture($ckoPaymentId);

            // Process partial capture if amount is less than grand total
            if ($amountLessThanGrandTotal) {
                $ckoPayment->amount = $amountCents;
                $ckoPayment->reference = $orderId;
            }

            $response = $checkout->payments()->capture($ckoPayment);

            if (!$response->isSuccessful()) {
                $errorMessage = 'An error has occurred while processing your capture payment on Checkout.com hub. Order Id : ' . $orderId;

                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

                Mage::throwException($errorMessage);
            } else {
                $payment->setTransactionId($response->id);
                $payment->setIsTransactionClosed(0);

                $order->setPaymentIsCaptured(1);
                $order->save();
            }
        } catch (CheckoutModelException $ex) {
            $errorMessage = "An error has occurred while processing your capture request. ";
            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::log($ex->getBody(), Zend_Log::DEBUG, $this->_code . '.log', true);
            Mage::throwException($errorMessage);
        } catch (CheckoutHttpException $ex) {
            Mage::log($ex->getMessage(), Zend_Log::DEBUG, $this->_code . '.log', true);
            $errorMessage = "An error has occurred while processing your capture request. ";
            Mage::throwException($errorMessage);
        }


        return $this;
    }

    /**
     * Used to process refund from backend
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $ckoPaymentId = $payment->getAdditionalInformation('ckoPaymentId');
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        // Check if CKO payment id exist in order
        if (empty($ckoPaymentId)) {
            $errorMessage = 'CKO PaymentId not found for order Id : ' . $orderId;

            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::throwException($errorMessage);
        }

        $currencyCode = $order->getOrderCurrencyCode();
        $grandTotals = $order->getGrandTotal();
        $grandTotalsCents = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($grandTotals, $currencyCode);
        $amountCents = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($amount, $currencyCode);

        $amountLessThanGrandTotal = $amountCents < $grandTotalsCents ? true : false;

        $environment =  Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        // Initialize the Checkout Api
        $checkout = new CheckoutApi($this->_getSecretKey(), $environment);

        try {
            // Check if payment is already voided or captured on checkout.com hub
            $details = $checkout->payments()->details($ckoPaymentId);

            if ($details->status == 'Refunded' && !$amountLessThanGrandTotal) {
                $errorMessage = 'Payment has already been refunded on Checkout.com hub for order Id : ' . $orderId;

                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::throwException($errorMessage);

                return $this;
            }

            $ckoPayment = new Refund($ckoPaymentId);

            // Process partial refund if amount is less than grand total
            if ($amountLessThanGrandTotal) {
                $ckoPayment->amount = $amountCents;
                $ckoPayment->reference = $orderId;
            }

            $response = $checkout->payments()->refund($ckoPayment);

            if (!$response->isSuccessful()) {
                $errorMessage = 'An error has occurred while processing your capture payment on Checkout.com hub. Order Id : ' . $orderId;

                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

                Mage::throwException($errorMessage);
            } else {
                $order->setPaymentIsRefunded(1);
                $order->save();
            }
        } catch (CheckoutModelException $ex) {
            $errorMessage = "An error has occurred while processing your refund request. ";
            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::log($ex->getBody(), Zend_Log::DEBUG, $this->_code . '.log', true);
            Mage::throwException($errorMessage);
        } catch (CheckoutHttpException $ex) {
            Mage::log($ex->getMessage(), Zend_Log::DEBUG, $this->_code . '.log', true);
            $errorMessage = "An error has occurred while processing your refund request. ";
            Mage::throwException($errorMessage);
        }


        return $this;
    }

    /**
     * Used to process void from backend
     *
     * @param Varien_Object $payment
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function void(Varien_Object $payment)
    {
        $ckoPaymentId = $payment->getAdditionalInformation('ckoPaymentId');
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        // Check if CKO payment id exist in order
        if (empty($ckoPaymentId)) {
            $errorMessage = 'CKO PaymentId not found for order Id : ' . $orderId;

            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::throwException($errorMessage);
        }

        $environment =  Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        // Initialize the Checkout Api
        $checkout = new CheckoutApi($this->_getSecretKey(), $environment);

        try {
            // Check if payment is already voided or captured on checkout.com hub
            $details = $checkout->payments()->details($ckoPaymentId);

            if ($details->status == 'Voided' || $details->status == 'Captured') {
                $errorMessage = 'Payment has already been voided or captured on Checkout.com hub for order Id : ' . $orderId;

                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::throwException($errorMessage);

                return $this;
            }

            // Prepare void payload
            $ckoPayment = new Voids($ckoPaymentId, $orderId);

            // Process void payment on checkout.com
            $response = $checkout->payments()->void($ckoPayment);

            if (!$response->isSuccessful()) {
                $errorMessage = 'An error has occurred while processing your void payment on Checkout.com hub. Order Id : ' . $orderId;

                Mage::log($errorMessage, null, $this->_code . '.log');
                Mage::log($response, Zend_Log::DEBUG, $this->_code . '.log', true);

                Mage::throwException($errorMessage);
            } else {
                $order->setPaymentIsVoided(1);
                $order->save();
            }
        } catch (CheckoutModelException $ex) {
            $errorMessage = "An error has occurred while processing your void request. ";
            Mage::log($errorMessage, null, $this->_code . '.log');
            Mage::log($ex->getBody(), Zend_Log::DEBUG, $this->_code . '.log', true);
            Mage::throwException($errorMessage);
        } catch (CheckoutHttpException $ex) {
            Mage::log($ex->getMessage(), Zend_Log::DEBUG, $this->_code . '.log', true);
            $errorMessage = "An error has occurred while processing your void request. ";
            Mage::throwException($errorMessage);
        }

        return $this;
    }
}
