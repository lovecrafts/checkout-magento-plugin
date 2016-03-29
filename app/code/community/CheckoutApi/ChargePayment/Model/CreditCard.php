<?php

/**
 * Class for CreditCard payment method
 *
 * Class CheckoutApi_ChargePayment_Model_CreditCard
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Model_CreditCard extends CheckoutApi_ChargePayment_Model_Checkout
{
    protected $_code        = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD;

    protected $_formBlockType = 'chargepayment/form_checkoutapicard';
    protected $_infoBlockType = 'chargepayment/info_checkoutapicard';

    /* Const for API */
    const TRANSACTION_INDICATOR_REGULAR     = 1;

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     *
     * @version 20151027
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info   = $this->getInfoInstance();
        $ccType = $data->getCcType();

        $info->setCcType($ccType)
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear())
            ->setCheckoutApiCardId('');

        $result = $this->_getSavedCartDataFromPost($data);

        if (!empty($result)) {
            $info->setCcLast4($result['cc_number']);
            $info->setCcType($result['cc_type']);
            $info->setCheckoutApiCardId($result['checkout_api_card_id']);
        }

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     *
     * @version 20151006
     */
    public function validate()
    {
        /**
         * Simple validate cart number
        */
        $info       = $this->getInfoInstance();
        $ccNumber   = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        if (!$this->validateCcNum($ccNumber)) {
            Mage::throwException(Mage::helper('chargepayment')->__('Invalid Credit Card Number'));
        }

        /**
         * to validate payment method is allowed for billing country or not
         */
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $billingCountry     = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
            $billingTelephone   = $paymentInfo->getOrder()->getBillingAddress()->getTelephone();
            $shippingTelephone  = $paymentInfo->getOrder()->getShippingAddress()->getTelephone();
        } else {
            $billingCountry     = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
            $billingTelephone   = $paymentInfo->getQuote()->getBillingAddress()->getTelephone();
            $shippingTelephone  = $paymentInfo->getQuote()->getShippingAddress()->getTelephone();
        }

        if (!$this->canUseForCountry($billingCountry)) {
            Mage::throwException(Mage::helper('chargepayment')->__('Selected payment type is not allowed for billing country.'));
        }

        /**
         * Validate phone numbers
         */
        if (!$this->_validateTelephone($billingTelephone) || !$this->_validateTelephone($shippingTelephone)) {
            Mage::throwException(Mage::helper('chargepayment')->__('Invalid Phone Number Format.'));
        }

        return $this;
    }

    /**
     * Authorize payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     *
     * @version 20151006
     */
    public function authorize(Varien_Object $payment, $amount) {
        $isDebug        = $this->isDebug();
        $autoCapture    = $this->_isAutoCapture();

        $Api        = CheckoutApi_Api::getApi(array('mode'=>$this->getEndpointMode()));
        $order      = $payment->getOrder();
        $amount     = $order->getGrandTotal();
        $amount     = $Api->valueToDecimal($amount, $order->getOrderCurrencyCode());
        $cardCharge = $this->_getCardCharge($payment, $amount);

        try {
            $result = $Api->createCharge($cardCharge);
        } catch (Exception $e) {
            Mage::log('Please make sure connection failures are properly logged. Action - Authorize', null, $this->_code.'.log');
        }

        if (is_object($result) && method_exists($result, 'toArray')) {
            Mage::log($result->toArray(), null, $this->_code.'.log');
        }

        if($result->isValid()) {
            if ($this->_responseValidation($result)) {
                /* Save Customer Credit Cart */
                $redirectUrl    = $result->getRedirectUrl();
                $entityId       = $result->getId();
                $session        = Mage::getSingleton('chargepayment/session_quote');

                /* is 3D payment */
                if ($redirectUrl && $entityId) {
                    $payment->setAdditionalInformation('payment_token', $entityId);
                    $payment->setAdditionalInformation('payment_token_url', $redirectUrl);

                    $session
                        ->setPaymentToken($entityId)
                        ->setIs3d(true)
                        ->setPaymentRedirectUrl($redirectUrl)
                        ->setEndpointMode($this->getEndpointMode())
                        ->setSecretKey($this->_getSecretKey())
                        ->setNewOrderStatus($this->getNewOrderStatus())
                    ;
                } else {
                    Mage::getModel('chargepayment/customerCard')->saveCard($payment, $result);

                    $payment->setTransactionId($entityId);
                    $payment->setIsTransactionClosed(0);

                    if ($autoCapture) {
                        $payment->setIsTransactionPending(true);
                    }

                    $session->setIs3d(false);
                }
            }
        } else {
            if ($isDebug) {
                /* Authorize processing error response. */
                $errors             = $result->toArray();

                if (!empty($errors['errorCode'])) {
                    $responseCode       = (int)$errors['errorCode'];
                    $responseMessage    = (string)$errors['message'];
                    $errorMessage       = "Error Code - {$responseCode}. Message - {$responseMessage}.";
                } else {
                    $errorMessage = Mage::helper('chargepayment')->__('Authorize action is not available.');
                }
            } else {
                $errorMessage = Mage::helper('chargepayment')->__('Authorize action is not available.');
            }

            Mage::throwException($errorMessage);
            Mage::log($result->printError(), null, $this->_code.'.log');
        }

        return $this;
    }

    /**
     * For validation billing or shipping phone numbers
     *
     * @param $phone
     * @return bool
     *
     * @version 20151007
     */
    protected function _validateTelephone($phone) {
        return strlen($phone) >= 7 ? true : false;
    }

    /**
     * Return array for Charge With Full Card
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return array
     *
     * @version 20151007
     */
    protected function _getCardCharge(Varien_Object $payment, $amount) {
        $secretKey      = $this->_getSecretKey();

        if (!$secretKey) {
            Mage::throwException(Mage::helper('chargepayment')->__('Payment method is not available.'));
        }

        $config         = array();
        $order          = $payment->getOrder();

        if (!$order) {
            Mage::throwException(Mage::helper('chargepayment')->__('Payment method is not available.'));
        }

        $autoCapture    = $this->_isAutoCapture();

        /* START: Prepare data */
        $billingAddress     = $order->getBillingAddress();
        $shippingAddress    = $order->getShippingAddress();
        $orderedItems       = $order->getAllItems();
        $currencyDesc       = $order->getOrderCurrencyCode();
        $orderId            = $order->getIncrementId();

        $street = Mage::helper('customer/address')
            ->convertStreetLines($billingAddress->getStreet(), 2);

        $billingAddressConfig = array (
            'addressLine1'  => $street[0],
            'addressLine2'  => $street[1],
            'postcode'      => $billingAddress->getPostcode(),
            'country'       => $billingAddress->getCountry(),
            'city'          => $billingAddress->getCity(),
            'state'         => $billingAddress->getRegion(),
            'phone'         => array('number' => $billingAddress->getTelephone())
        );

        $street = Mage::helper('customer/address')
            ->convertStreetLines($shippingAddress->getStreet(), 2);

        $shippingAddressConfig = array(
            'addressLine1'  => $street[0],
            'addressLine2'  => $street[1],
            'postcode'      => $shippingAddress->getPostcode(),
            'country'       => $shippingAddress->getCountry(),
            'city'          => $shippingAddress->getCity(),
            'state'         => $shippingAddress->getRegion(),
            'phone'         => array('number' => $shippingAddress->getTelephone()),
        );

        $products = array();

        foreach ($orderedItems as $item) {
            $product        = Mage::getModel('catalog/product')->load($item->getProductId());
            $productImage   = $product->getImage();

            $products[] = array(
                'description'   => $product->getShortDescription(),
                'image'         => $productImage != 'no_selection' && !is_null($productImage) ? Mage::helper('catalog/image')->init($product , 'image')->__toString() : '',
                'name'          => $item->getName(),
                'price'         => $item->getPrice(),
                'quantity'      => $item->getQtyOrdered(),
                'sku'           => $item->getSku()
            );
        }

        /* END: Prepare data */

        $config['autoCapTime']  = self::AUTO_CAPTURE_TIME;
        $config['autoCapture']  = $autoCapture ? CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE : CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH;
        $config['chargeMode']   = $this->getChargeMode();

        $email                  = $shippingAddress->getEmail();
        $email                  = !empty($email) ? $email : $order->getCustomerEmail();
        $config['email']        = $email;
        $config['description']  = 'charge description';

        $config['value']                = $amount;
        $config['currency']             = $currencyDesc;
        $config['trackId']              = $orderId;
        $config['transactionIndicator'] = self::TRANSACTION_INDICATOR_REGULAR;
        $config['customerIp']           = Mage::helper('core/http')->getRemoteAddr();

        /* Charge with Card ID if it set */
        $checkoutApiCardId = $payment->getCheckoutApiCardId();

        if (!empty($checkoutApiCardId)) {
            $config['cardId'] = $checkoutApiCardId;
        } else {
            $config['card'] = array(
                'name'              => $payment->getCcOwner(),
                'number'            => $payment->getCcNumber(),
                'expiryMonth'       => $payment->getCcExpMonth(),
                'expiryYear'        => $payment->getCcExpYear(),
                'cvv'               => $payment->getCcCid(),
                'billingDetails'    => $billingAddressConfig
            );
        }

        $config['shippingDetails']  = $shippingAddressConfig;
        $config['products']         = $products;

        $result['authorization']    = $secretKey;
        $result['postedParam']      = $config;

        return $result;
    }

    /**
     * For check if user have saved cards
     *
     * @param $customerId
     * @return bool
     *
     * @version 20151027
     */
    public function isCanUseSavedCard($customerId) {
        if (empty($customerId)) {
            return false;
        }

        $collection = Mage::getModel('chargepayment/customerCard')->getCustomerCardList($customerId);

        return $collection->count() ? true : false;
    }

    /**
     * For validate customer saved card from POST
     *
     * @param $data
     * @return array
     * @throws Mage_Core_Exception
     *
     * @version 20151027
     */
    protected function _getSavedCartDataFromPost($data) {
        $savedCard  = $data->getCustomerCard();
        $card       = $data->getCcNumber();

        if (empty($savedCard) && empty($card)) {
            Mage::throwException(Mage::helper('chargepayment')->__('Please check your card data.'));
        }

        /* If non saved card */
        if (empty($savedCard) || $savedCard === 'new_card') {
            return array();
        }

        $customerId = $this->getCustomerId();

        /* If user not logged */
        if (empty($customerId)) {
            return array();
        }

        $cardModel  = Mage::getModel('chargepayment/customerCard');
        $collection = $cardModel->getCustomerCardList($customerId);

        /* If user not have saved cards */
        if (!$collection->count()) {
            return array();
        }

        $trueData       = false;
        $customerCard   = array();

        foreach($collection as $entity) {
            $secret = $cardModel->getCardSecret($entity->getId(), $entity->getCardNumber(), $entity->getCardType());

            if ($savedCard === $secret) {
                $trueData = true;
                $customerCard = $entity;
                break;
            }
        }

        if (!$trueData) {
            Mage::throwException(Mage::helper('chargepayment')->__('Please check your card data.'));
        }

        $result['cc_number']            = $customerCard->getCardNumber();
        $result['cc_type']              = $customerCard->getCardType();
        $result['checkout_api_card_id'] = $customerCard->getCardId();

        return $result;
    }

    /**
     * Return Customer Id from quote for backend and frontend
     *
     * @return bool
     *
     * @version 20151028
     */
    public function getCustomerId() {
        if (Mage::app()->getStore()->isAdmin()) {
            $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
        } else {
            $customerId = Mage::getModel('checkout/cart')->getQuote()->getCustomerId();
        }

        return $customerId ? $customerId : false;
    }

    /**
     * Return Yes if we must show select with CcTypes
     *
     * @return mixed
     *
     * @version 20160111
     */
    public function getIsVisibleCcType() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'cctype_visible');
    }

    /**
     * Check if admin user create
     *
     * @return bool
     *
     * @version 20160209
     */
    protected function _isAdminPayment() {
        $isAdminArea    = (string)Mage::getDesign()->getArea();
        $isAdmin        = Mage::app()->getStore()->isAdmin();

        return $isAdminArea === 'adminhtml' && $isAdmin ? true : false;
    }

    /**
     * Return Quote Session for Admin or Frontend
     *
     * @return Mage_Core_Model_Abstract
     *
     * @version 20160209
     */
    protected function _getQuote() {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * return Charge Mode
     *
     * @return int
     *
     * @version 20160215
     */
    public function getChargeMode() {
        return CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_NOT_3D;
    }
}