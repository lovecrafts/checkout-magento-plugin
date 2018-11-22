<?php

/**
 * Controller for Checkout.com Webhooks
 *
 * Class CheckoutApi_ChargePayment_ApiController
 *
 * @version 20151113
 */
class CheckoutApi_ChargePayment_ApiController extends Mage_Core_Controller_Front_Action
{
    /**
     * Routing for webhooks from Checkout.com
     *
     * @url chargepayment/api/webhook/
     *
     * @version 20151113
     */
    public function webhookAction()
    {
        $modelWebhook   = Mage::getModel('chargepayment/webhook');

        $isDebugCard    = Mage::getModel('chargepayment/creditCard')->isDebug();
        $isDebugJs      = Mage::getModel('chargepayment/creditCardJs')->isDebug();
        $isDebugKit     = Mage::getModel('chargepayment/creditCardKit')->isDebug();
        $isDebugHosted  = Mage::getModel('chargepayment/hosted')->isDebug();
        $isDebugGPay    = Mage::getModel('chargepayment/googlePay')->isDebug();
        $isDebugApplePay = Mage::getModel('chargepayment/applePay')->isDebug();
        $isDebugFrames  = Mage::getModel('chargepayment/creditCardFrames')->isDebug();

        $isDebug        = $isDebugCard || $isDebugJs || $isDebugKit || $isDebugHosted || $isDebugGPay || $isDebugApplePay || $isDebugFrames ? true : false;

        if ($isDebug) {
            Mage::log(file_get_contents('php://input'), null, CheckoutApi_ChargePayment_Model_Webhook::LOG_FILE);
            Mage::log(json_decode(file_get_contents('php://input')), null, CheckoutApi_ChargePayment_Model_Webhook::LOG_FILE);
        }

        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(400);
            return;
        }

        $request        = new Zend_Controller_Request_Http();
        $key            = $request->getHeader('Authorization');

        if (!$modelWebhook->isValidPublicKey($key)) {
            $this->getResponse()->setHttpResponseCode(401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'));

        if (empty($data)) {
            $this->getResponse()->setHttpResponseCode(400);
            return;
        }

        $eventType          = $data->eventType;

        switch ($eventType) {
            case CheckoutApi_ChargePayment_Model_Webhook::EVENT_TYPE_CHARGE_SUCCEEDED:
                $result = $modelWebhook->authoriseOrder($data);
                break;
            case CheckoutApi_ChargePayment_Model_Webhook::EVENT_TYPE_CHARGE_CAPTURED:
                $result = $modelWebhook->captureOrder($data);
                break;
            case CheckoutApi_ChargePayment_Model_Webhook::EVENT_TYPE_CHARGE_REFUNDED:
                $result = $modelWebhook->refundOrder($data);
                break;
            case CheckoutApi_ChargePayment_Model_Webhook::EVENT_TYPE_CHARGE_VOIDED:
                $result = $modelWebhook->voidOrder($data);
                break;
            case CheckoutApi_ChargePayment_Model_Webhook::EVENT_TYPE_INVOICE_CANCELLED:
                $result = $modelWebhook->voidOrder($data);
                break;
            default:
                $message = $eventType. ' - event not handle for chargeId : '. $data->message->id;
                Mage::log($message, null, CheckoutApi_ChargePayment_Model_Webhook::LOG_FILE);
                
                $result = $this->getResponse()->setHttpResponseCode(200);
                return;
        }

        $httpCode = $result ? 200 : 400;

        $this->getResponse()->setHttpResponseCode($httpCode);
    }

    /**
     * Action for verify charge by payment token
     *
     * @url chargepayment/api/callback/?cko-payment-token=payment_token
     *
     * @version 20160219
     */
    public function callbackAction() {
        $responseToken  = (string)$this->getRequest()->getParam('cko-payment-token');
        $session        = Mage::getSingleton('chargepayment/session_quote');
        $isLocalPayment = $session->isCheckoutLocalPaymentTokenExist($responseToken);

        $modelWebhook   = Mage::getModel('chargepayment/webhook');
        $helper         = Mage::helper('chargepayment');

        if ($responseToken) {

            if ($isLocalPayment) {
                $this->_redirect('chargepayment/api/complete', array('_query' => 'token=' . $responseToken));
                return;
            }

            $result = $modelWebhook->authorizeByPaymentToken($responseToken);
            $order = Mage::getModel('sales/order')->loadByIncrementId($result['order_increment_id']);

            if ($result['is_admin'] === false) {
                $redirectUrl    = 'checkout/onepage/success';

                if ($result['error'] === true) {
                    $redirectUrl = Mage::helper('checkout/url')->getCheckoutUrl();
                    Mage::getSingleton('core/session')->addError('Please check you card details and try again. Thank you');

                    if(!is_null($result['order_increment_id'])){
                        $order->cancel();
                        $order->addStatusHistoryComment('Order has been cancelled.');
                        $order->save();

                        /* Restore quote session */
                        $helper->restoreQuoteSession($order);
                    }

                    $this->_redirectUrl($redirectUrl);
                    return;
                }

                $order->sendNewOrderEmail();
                $this->_redirect($redirectUrl);
            }

            return;
        }
    }

    /**
     * Fail page
     *
     * @url checkout/url
     *
     * @version 20161012
     */
    public function failAction(){
        $session        = Mage::getSingleton('chargepayment/session_quote');
        $redirectUrl    = Mage::helper('checkout/url')->getCheckoutUrl();

        $lastOrderIncrementId = $session->LastOrderIncrementId;

        if(is_null($LastOrderIncrementId)){
             $lastOrderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        Mage::getSingleton('core/session')->addError('Please check your payment details and try again. Thank you');

        if(!is_null($lastOrderIncrementId)){
            $order = Mage::getModel('sales/order')->loadByIncrementId($lastOrderIncrementId);
            $order->cancel();
            $order->setStatus('canceled');
            $order->setState('canceled');
            $order->addStatusHistoryComment('Order has been cancelled.');
            $order->save();

            $helper             = Mage::helper('chargepayment');
            $helper->restoreQuoteSession($order);
        }

        return $this->_redirectUrl($redirectUrl);
    }

    /**
     * Local Payment Complete Page
     *
     * @url chargepayment/api/complete
     *
     * @return Mage_Core_Controller_Varien_Action
     *
     * @version 20160426
     */
    public function completeAction() {
        $responseToken  = (string)$this->getRequest()->getParam('token');

        if (!$responseToken) {
            $this->norouteAction();
            return;
        }

        $session        = Mage::getSingleton('chargepayment/session_quote');
        $isLocalPayment = $session->isCheckoutLocalPaymentTokenExist($responseToken);

        if (!$isLocalPayment) {
            $this->norouteAction();
            return;
        }

        /* Clear checkout */
        Mage::getSingleton('checkout/session')->clear();

        $cart = Mage::getModel('checkout/cart');
        $cart->truncate()->save();

        $session->removeCheckoutLocalPaymentToken($responseToken);

        $this->loadLayout();

        $this->getLayout()
            ->getBlock('head')
            ->setTitle($this->__('Local Payment Completed (Checkout.com)'));

        $this->renderLayout();
    }

    /**
     * Action for verify charge by card token
     *
     * @url chargepayment/api/hosted/
     */
    public function hostedAction() { 
        $cardToken          = (string)$this->getRequest()->getParam('cko-card-token');

        if(!$cardToken){
            $cardToken = Mage::getSingleton('core/session')->getHostedCardId();
        }

        $orderIncrementId   = (string)$this->getRequest()->getParam('cko-context-id');

        if(!$orderIncrementId){
            $orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        $order              = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $helper             = Mage::helper('chargepayment');

        if (!$order->getId()) {
            $this->norouteAction();
            return;
        }

        if (!$cardToken) {
            Mage::getSingleton('core/session')->addError('Your payment has been cancelled. Please enter your card details and try again.');
            $result = array('status' => 'error', 'redirect' => Mage::helper('checkout/url')->getCheckoutUrl());
            $order->cancel();
            $order->addStatusHistoryComment('Order has been cancelled.');
            $order->save();

            /* Restore quote session */
            $helper->restoreQuoteSession($order);

            $this->_redirectUrl($result['redirect']);
            return;
        }

        $hostedModel    = Mage::getModel('chargepayment/hosted');

        $result         = $hostedModel->authorizeByCardToken($order, $cardToken);
        $session        = Mage::getSingleton('chargepayment/session_quote');

        switch($result['status']) {
            case 'success':
                $session
                    ->setHostedPaymentRedirect(NULL)
                    ->setHostedPaymentParams(NULL)
                    ->setHostedPaymentConfig(NULL)
                    ->setSecretKey(NULL)
                    ->setCcId(NULL);
                    
                Mage::getSingleton('core/session')->unsHostedCardId();
                
                $this->_redirect($result['redirect']);

                break;
            case '3d':
                $session
                    ->setHostedPaymentRedirect(NULL)
                    ->setHostedPaymentConfig(NULL)
                    ->setHostedPaymentParams(NULL)
                    ->setCcId(NULL);;

                $this->_redirectUrl($result['redirect']);
                break;
            case 'error':
                Mage::getSingleton('core/session')->addError('Please check you card details and try again. Thank you');
                $order->cancel();
                $order->addStatusHistoryComment('Order has been cancelled.');
                $order->save();

                /* Restore quote session */
                $helper->restoreQuoteSession($order);
                $this->_redirectUrl($result['redirect']);
                break;
            default:
                Mage::getSingleton('core/session')->addError('Something went wrong. Kindly contact us for more details.');
                // /* Restore quote session */
                $helper->restoreQuoteSession($order);

                $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                break;
        }

        return $this;
    }

    /**
     * Redirect Action for Hosted Payment
     *
     * @url chargepayment/api/redirect
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function redirectAction() {
        $session        = Mage::getSingleton('chargepayment/session_quote');
        $redirectUrl    = $session->getHostedPaymentRedirect();

        if (empty($redirectUrl)) {
            $this->norouteAction();
            return $this;
        }

        $this->loadLayout();
        $this->renderLayout();

    }

    public function requestMerchantSessionAction(){
        $params = $this->getRequest()->getParams();

        $validationURL = $params['validationURL'];
        $merchantIdentifier = Mage::getModel('chargepayment/applePay')->getApplePayMerchantIdentifier();
        $domainName = $_SERVER['SERVER_NAME'];
        $displayName = Mage::app()->getStore()->getName();
        $applePayCertPath = Mage::getModel('chargepayment/applePay')->getApplePayCertPath();
        $applePayCertKey = Mage::getModel('chargepayment/applePay')->getApplePayCertKey();

        $data = '{
            "merchantIdentifier":"'. $merchantIdentifier . '",
            "domainName":"'. $domainName . '",
            "displayName":"'. $displayName . '"
        }';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $validationURL);
        curl_setopt($ch, CURLOPT_SSLCERT, $applePayCertPath);
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,"PEM");
        curl_setopt($ch, CURLOPT_SSLKEY, $applePayCertKey);
        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
       
        $result = curl_exec($ch);

        if($result === false)
        {
            $message =  '{"curlError":"' . curl_error($ch) . '"}';

            $msg = "\n(Network error [errno $errno]: $message)";
            Mage::log($msg, null, $this->_code.'.log');
            throw new \Exception($msg);
            
        }

        curl_close($ch);
        return $result;
        
    }

    public function sendPaymentAction(){
        $params = $this->getRequest()->getParams();
        $payment = json_decode($params['payment']);
        $applePayPaymentData = $payment->paymentData;

        if(empty($applePayPaymentData)){
            $errorMessage = 'Network error. Empty payment data';
            Mage::log('Empty applePayPaymentData', null, 'applepay.log');
            $this->getResponse()->setBody("ERROR");
            return $this;
        }

        $publicKey = Mage::getModel('chargepayment/applePay')->getPublicKey();

        $endPointMode = Mage::helper('chargepayment')->getConfigData('checkoutapiapplepay', 'mode'); 
        $createTokenUrl = "https://sandbox.checkout.com/api2/tokens";

        if($endPointMode == 'live'){
            $createTokenUrl = "https://api2.checkout.com/tokens";
        }

        $config = array(
            'type' => 'applepay',
            'token_data' => (array) $applePayPaymentData 
        );

        // curl to create apple pay token.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $createTokenUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: '.$publicKey,
            'Content-Type:application/json;charset=UTF-8'
            ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $server_output = curl_exec($ch);
        curl_close ($ch);

        $response = json_decode($server_output);

        if(!empty($response->token)){
            $ckoPaymentData = json_decode($params['paymentData']);
            $postedParam = (array) $ckoPaymentData->postedParam;
            $postedParam['cardToken'] = $response->token;
            $secretKey = Mage::getModel('chargepayment/applePay')->getSecretKey();

            $createChargeUrl = "https://sandbox.checkout.com/api2/v2/charges/token";

            if($endPointMode == 'live'){
                $createChargeUrl = "https://api2.checkout.com/v2/charges/token";
            }

             // curl to create apple pay charge at cko
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$createChargeUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: '.$secretKey,
                'Content-Type:application/json;charset=UTF-8'
                ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postedParam));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $server_output = curl_exec($ch);
            curl_close ($ch);

            $response = json_decode($server_output);

            if($response){ 
                if (preg_match('/^1[0-9]+$/', $response->responseCode)) {
                    $orderId = $this->createApplePayOrder($ckoPaymentData, $response);

                    if($orderId){
                        $updateChargeUrl = "https://sandbox.checkout.com/api2/v2/charges/".$response->id;

                        if($endPointMode == 'live'){
                            $updateChargeUrl = "https://api2.checkout.com/v2/charges/".$response->id;
                        }

                        $data = array("trackId" => $orderId);
                        $request_headers = array();
                        $request_headers[] = 'Authorization: '. $secretKey;
                        $request_headers[] = 'content: application/json';

                        $ch = curl_init($updateChargeUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

                        $response = curl_exec($ch);

                        if (curl_error($ch)) {
                            $result = "ERROR";
                            $error_msg = curl_error($ch);
                            $errorMessage = 'Curl error : '.$error_msg;
                            Mage::log('charge declined, chargeId : '. $response->id, null, 'applepay.log');
                            $this->getResponse()->setBody($result);
                            return $this;
                        }
                    }

                    $result = "SUCCESS";
                    $this->getResponse()->setBody($result);
                    return $this;

                } else {
                    $result = "ERROR";
                    $errorMessage = 'An error has occured, please verify your payment details and try again.';
                    Mage::log('charge declined, chargeId : '. $response->id, null, 'applepay.log');
                  
                    $this->getResponse()->setBody($result);
                    return $this;
                }
            }

        } else {
            $errorMessage = 'An error has occured, please verify your payment details and try again.';
            Mage::log('Empty Apple pay token', null, 'applepay.log');
            Mage::throwException($errorMessage);
        }
    }

    public function createApplePayOrder($params, $response){ 
        $storeID = Mage::app()->getStore()->getId();
        $store = Mage::getModel('core/store')->load($storeID);
        $websiteId = $store->getWebsiteId();
     
        // Start New Sales Order Quote
        $quote = Mage::getModel('sales/quote')->setStoreId($storeID);

        // Set Sales Order Quote Currency
        $quote->setCurrency(Mage::app()->getStore($storeID)->getCurrentCurrencyCode());

        $postedParam = (array) $params->postedParam;       
     
        // Get customer
        $customer = Mage::getModel('customer/customer')
        ->setWebsiteId($websiteId)
        ->loadByEmail($postedParam['email']);
    
     
        //Create customer in case that it not exists
        if (empty($customer->getData())) {
            $customer = Mage::getModel('customer/customer');
     
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($customerData['firstname'])
                ->setLastname($customerData['lastname'])
                ->setEmail($customerData['email'])
                ->setPassword($customerData['password']);
     
            $customer->save();
        }
     
        // Assign Customer To Sales Order Quote
        $quote->assignCustomer($customer);
     
        // Configure Notification
        $quote->setSendCconfirmation(1);

        foreach($postedParam['products'] as $productItem){
            $productItems = (array) $productItem;
            $product = Mage::helper('catalog/product')->getProduct($productItems['sku'], $storeID, "sku" );
            // You can replace the above line, with the procuct id.
            // $product = Mage::helper('catalog/product')->getProduct($productItems['sku'], $storeID, "id" ); 
            $quote->addProduct($product, new Varien_Object(array('qty' => $productItems['quantity'])));
        }

        $billingDetails = (array) $postedParam['billingDetails'];
        $billingPhone = (array) $billingDetails['phone'];

        $shippingDetails = (array) $postedParam['shippingDetails'];
        $shippingPhone = (array) $shippingDetails['phone'];
       
        // Set Sales Order Billing Address
        $quote->getBillingAddress()->addData(array(
        'customer_address_id' => '',
        'prefix' => '',
        'firstname'  => $customer->getFirstname(),
        'middlename' => $customer->getMiddlename(),
        'lastname'   => $customer->getLastname(),
        'suffix' => '',
        'company' =>'',
        'street' => array(
            '0' => $billingDetails['addressLine1'],
            '1' => $billingDetails['addressLine2']
        ),
        'city' => $billingDetails['city'],
        'country_id' => $billingDetails['country'],
        'region' => $billingDetails['state'],
        'postcode' => $billingDetails['postcode'],
        'telephone' => $billingPhone['number'],
        // 'fax' => billingData['fax'],
        'vat_id' => '',
        'save_in_address_book' => 1
        ));


        // Set Sales Order Shipping Address
        $shippingAddress = $quote->getShippingAddress()->addData(array(
        'customer_address_id' => '',
        'prefix' => '',
        'firstname' => $customer->getFirstname(),
        'middlename' => $customer->getMiddlename(),
        'lastname' => $customer->getLastname(),
        'suffix' => '',
        'company' =>'',
        'street' => array(
            '0' => $shippingDetails['addressLine1'],
            '1' => $shippingDetails['addressLine2']
        ),
        'city' => $shippingDetails['city'],
        'country_id' => $shippingDetails['country'],
        'region' => $shippingDetails['state'],
        'postcode' => $shippingDetails['postcode'],
        'telephone' => $shippingPhone['number'],
        // 'fax' => shippingData['fax'],
        'vat_id' => '',
        'save_in_address_book' => 1
        ));


        // Collect Rates and Set Shipping & Payment Method
        $shippingAddress->setCollectShippingRates(true)
        ->collectShippingRates()
        ->setShippingMethod($params->selectedShippingMethodCode);
     
        // Set payment method
        $quote->getPayment()->setMethod('checkoutapiapplepay');
     
        // Collect Totals & Save Quote
        $quote->collectTotals()->save();
     
        // Create Order From Quote
        $service = Mage::getModel('sales/service_quote', $quote);


        $service->submitAll();
        $order = $service->getOrder();

        $payment = $order->getPayment();

        $entityId = $response->id;

        $endPointMode = Mage::helper('chargepayment')->getConfigData('checkoutapiapplepay', 'mode');

        $Api    = CheckoutApi_Api::getApi(array('mode' => $endPointMode));
        $amount = $Api->decimalToValue($response->value, $response->currency);
        $autoCapture = Mage::helper('chargepayment')->getConfigData('checkoutapiapplepay', 'autoCapture');

        $payment
                ->setTransactionId($entityId)
                ->setCurrencyCode($response->currency)
                ->setPreparedMessage((string)$response->description)
                ->setIsTransactionClosed(0)
                ->setShouldCloseParentTransaction(false)
                ->setBaseAmountAuthorized($amount);
                // ->setAdditionalInformation('use_current_currency', $isCurrentCurrency);

        if ($autoCapture) {
            $message = Mage::helper('sales')->__('Capturing amount of %s is pending approval on gateway.', $amount);

            $payment->setIsTransactionPending(true);
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false , '');
        } else {
            $message = Mage::helper('sales')->__('Authorized amount of %s.', $amount);
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false , '');
        }
        
        $message .= ' ' . Mage::helper('sales')->__('Transaction ID: "%s".', $entityId);

        Mage::log('response code = '. CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED, null, 'applepay.log');

        if($response->responseCode == CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED ){
            $order->setStatus('pending');
            $order->addStatusHistoryComment($message, false);
        } else {
            $fraudmessage = $message.' '. Mage::helper('sales')->__(' Suspected fraud - Please verify amount and quantity.');
            $order->setState('payment_review');
            $order->setStatus('fraud');
            $order->addStatusHistoryComment($fraudmessage, false);
        }
        
        $order->save();
        $order->sendNewOrderEmail();
        
        $cart = Mage::getSingleton('checkout/cart');
        $cart->truncate()->save();
        
        $increment_id = $service->getOrder()->getRealOrderId();

        $session = Mage::getSingleton('checkout/session');
        $session->setLastOrderId($order->getId());
        $session->setLastRealOrderId($order->getIncrementId());
        $session->setLastSuccessQuoteId($order->getQuoteId());
        $session->setLastQuoteId($order->getQuoteId());
     
        Mage::dispatchEvent('sales_order_place_after', array('order' => $service->getOrder()));
     
        // Resource Clean-Up
        $quote = $customer = $service = null;   
 
        // Finished
        return $increment_id;

    }
}
