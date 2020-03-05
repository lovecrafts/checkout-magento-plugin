<?php

/**
 * Class Checkcoutcom_Ckopayment_CheckoutcomWebhook
 */
class Checkoutcom_Ckopayment_Model_CheckoutcomWebhook
{
    /**
     * Set message to order for webhook event payment_approved
     *
     * @param $webhookData
     * @return bool
     */
    public function voidPayment($webhookData)
    {
        $reference = $webhookData->data->reference;
        $modelOrder = Mage::getModel('sales/order');
        $order = $modelOrder->loadByIncrementId($reference);

        if (!$order->getPaymentIsVoided()) {
            $voidStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getVoidedOrderStatus();
            $authStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getAuthorisedOrderStatus();

            if ($voidStatus == 'canceled') {
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
            } else {
                if ($authStatus == 'pending') {
                    $order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
                }
            }
        }

        $message = "Webhook received from checkout.com. Payment authorised successfully";
        $order->addStatusToHistory(false, $message);
        $order->save();

        return true;
    }

    /**
     * Set message to order for webhook event payment_approved
     *
     * @param $webhookData
     * @return bool
     */
    public function capturePayment($webhookData)
    {
        $reference = $webhookData->data->reference;
        $modelOrder = Mage::getModel('sales/order');
        $order = $modelOrder->loadByIncrementId($reference);

        // check if payment already captured on backend.
        if (!$order->getPaymentIsCaptured()) {
            $captureStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getCapturedOrderStatus();
            $createInvoice = Mage::getModel('ckopayment/checkoutcomConfig')->getCreateInvoice();
            $amount = $webhookData->data->amount;
            $currencyCode = $order->getOrderCurrencyCode();
            $grandTotals = $order->getGrandTotal();
            $grandTotalsCents = Mage::getModel('ckopayment/checkoutcomUtils')
                ->valueToDecimal($grandTotals, $currencyCode);

            $amountLessThanGrandTotal = $amount < $grandTotalsCents ? true : false;

            $payment = $order->getPayment();
            $payment->setTransactionId($webhookData->data->action_id);
            $payment->setIsTransactionClosed(0);
            $payment->setCurrencyCode($order->getBaseCurrencyCode());
            
            if($amountLessThanGrandTotal){
                $amountDecimal = Mage::getModel('ckopayment/checkoutcomUtils')
                    ->decimalToValue($amount, $currencyCode);
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();

                // set message in order history for partial capture and update status
                $message = "Webhook received from checkout.com. An amount of {$currencySymbol}{$amountDecimal} has been partially captured on hub. No invoice created";
                $this->_addTransaction(
                        $payment,
                        $webhookData->data->action_id,
                        'capture',
                        array('is_transaction_closed' => 0),
                        array(
                            'real_transaction_id' => $webhookData->data->action_id
                        ),
                        false
                    );
            } else {
                if ($createInvoice) {
                    $message = "Webhook received from checkout.com. Payment captured on checkout.com hub";
                    $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                } else {
                    $message = "Webhook received from checkout.com. Payment captured on checkout.com hub. No invoice created";
                }
            }
            
            $order->setPaymentIsCaptured(1);
            $order->addStatusToHistory($captureStatus, $message);
            $order->save();


        } else {
            $message = "Webhook received from checkout.com. Payment captured on checkout.com hub";
            $order->addStatusToHistory(false, $message);
            $order->save();
        }

        $invoiceCollection = $order->getInvoiceCollection();
        foreach($invoiceCollection as $invoice) {
            $invoiceIncrementId =  $invoice->getIncrementId();
        }

        // Send invoice email
        if(!null == $invoiceIncrementId){
            $invoice->sendEmail($notifyCustomer=true, $comment='');
        }

        return true;
    }


    /**
     * Set message to order for webhook event payment_approved
     *
     * @param $webhookData
     * @return bool
     */
    public function refundPayment($webhookData)
    {
        $reference = $webhookData->data->reference;
        $modelOrder = Mage::getModel('sales/order');
        $order = $modelOrder->loadByIncrementId($reference);

        // check if payment already refunded on backend.
        if (!$order->getPaymentIsRefunded()) {
            $status = Mage::getModel('ckopayment/checkoutcomConfig')->getCapturedOrderStatus();
            $refundStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getRefundedOrderStatus();

            $createCreditMemo = Mage::getModel('ckopayment/checkoutcomConfig')->getCreateMemo();
            $amount = $webhookData->data->amount;
            $currencyCode = $order->getOrderCurrencyCode();
            $grandTotals = $order->getGrandTotal();
            $grandTotalsCents = Mage::getModel('ckopayment/checkoutcomUtils')
                ->valueToDecimal($grandTotals, $currencyCode);

            $amountLessThanGrandTotal = $amount < $grandTotalsCents ? true : false;

            $payment = $order->getPayment();
            $payment->setTransactionId($webhookData->data->action_id);
            $payment->setIsTransactionClosed(0);
            $payment->setCurrencyCode($order->getBaseCurrencyCode());

            if($amountLessThanGrandTotal){
                $amountDecimal = Mage::getModel('ckopayment/checkoutcomUtils')
                    ->decimalToValue($amount, $currencyCode);
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();

                // set message in order history for partial refund and update status
                $message = "Webhook received from checkout.com. An amount of {$currencySymbol}{$amountDecimal} has been partially refunded on hub. No invoice created";
                
            } else {
                if ($createCreditMemo) {
                    $message = "Webhook received from checkout.com. Payment refunded on checkout.com hub";

                    $service = Mage::getModel('sales/service_order', $order);
                    
                    $creditMemo = $service->prepareCreditmemo()
                        ->setPaymentRefundDisallowed(true)
                        ->setAutomaticallyCreated(true)
                        ->register()
                        ->save();
                    
                    $order->setPaymentIsRefunded(1);
                } else {
                    $message = "Webhook received from checkout.com. Payment refunded on checkout.com hub. No creditMemo created";
                }

                $status = $refundStatus;
            }
            
            $this->_addTransaction(
                        $payment,
                        $webhookData->data->action_id,
                        'refund',
                        array('is_transaction_closed' => 0),
                        array(
                            'real_transaction_id' => $webhookData->data->action_id
                        ),
                        false
                    );
            
            $order->addStatusToHistory($status, $message);
            $order->save();

        } else {
            $message = "Webhook received from checkout.com. Payment refunded successfully.";
            $order->addStatusToHistory(false, $message);
            $order->save();
        }


        return true;
    }

    /**
     * @param $webhookData
     * @return bool
     */
    public function cancelPayment($webhookData)
    {
        $paymentId = $webhookData->data->id;
        $secretKey = Mage::getModel('ckopayment/checkoutcomConfig')->getSecretKey();
        $environment = Mage::getModel('ckopayment/checkoutcomConfig')->getEnvironment() == 'sandbox' ? true : false;
        $checkout = new CheckoutApi($secretKey, $environment);

        $details = $checkout->payments()->details($paymentId);
        $reference = $details->reference;
        $modelOrder = Mage::getModel('sales/order');
        $order = $modelOrder->loadByIncrementId($reference);

        // Cancel order on magento
        $order->cancel();
        $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Webhook received from checkout.com. Payment cancelled.');
        $order->save();

        return true;
    }

    /**
    *
    * Register transaction id in magentol
    *
    */ 
    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType,
        array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }
        $payment->unsLastTransId();

        $transaction->setMessage($message);

        return $transaction;
    }
}