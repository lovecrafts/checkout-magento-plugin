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
        $voidStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getVoidedOrderStatus();
        $authStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getAuthorisedOrderStatus();
        $actionId = $webhookData->data->action_id;
        $reference = $webhookData->data->reference;
        $status = $voidStatus == 'canceled' ? Mage_Sales_Model_Order::STATE_CANCELED : Mage_Sales_Model_Order::STATE_NEW;

        if (!$order->getPaymentIsVoided()) {
            
            $modelOrder = Mage::getModel('sales/order');
            $order = $modelOrder->loadByIncrementId($reference);
    
            $payment = $order->getPayment();
            $parentTransactionId = Mage::getModel('ckopayment/checkoutcomCards')->getCkoParentTransId('Authorization', $webhookData->data->id);
    
            $payment->setTransactionId($actionId);
            $payment->setParentTransactionId($parentTransactionId);
            $payment->registerVoidNotification($order->getBaseGrandTotal());

            $order->setState($status, true);
        }

        $order->save();

        return true;
    }
        
    /**
     * Set message to order for webhook event payment_captured
     *
     * @param  mixed $webhookData
     * @return void
     */
    public function capturePayment($webhookData)
    {
        $reference = $webhookData->data->reference;
        $modelOrder = Mage::getModel('sales/order');
        $order = $modelOrder->loadByIncrementId($reference);
        $actionId = $webhookData->data->action_id;

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

            $parentTransactionId = Mage::getModel('ckopayment/checkoutcomCards')->getCkoParentTransId('Authorization', $webhookData->data->id);

            $payment = $order->getPayment();
            $payment->setTransactionId($webhookData->data->action_id);
            $payment->setParentTransactionId($parentTransactionId);
            $payment->setIsTransactionClosed(0);
            $payment->setCurrencyCode($order->getBaseCurrencyCode());
            
            if ($amountLessThanGrandTotal) {
                $amountDecimal = Mage::getModel('ckopayment/checkoutcomUtils')
                    ->decimalToValue($amount, $currencyCode);
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
                $refundAmount = $currencySymbol.$amountDecimal;

                // set message in order history for partial capture and update status
                $message = "Webhook received from checkout.com. Registered notification about capture amount of {$refundAmount}. Transaction ID: {$actionId}. Invoice has not been created. Please create offline invoice.";

                $this->_addTransaction(
                    $payment,
                    $actionId,
                    'refund',
                    array('is_transaction_closed' => 0),
                    array('real_transaction_id' => $actionId),
                    false
                );

                $order->addStatusToHistory($captureStatus, $message);

            } else {
                if ($createInvoice) {
                    $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                    $payment->setShouldCloseParentTransaction(true);
                } else {
                    $message = "Webhook received from checkout.com. Payment captured on checkout.com hub. No invoice created";
                    $order->addStatusToHistory($captureStatus, $message);
                }
            }
            
            $order->setPaymentIsCaptured(1);
            $order->save();

        } else {
            $message = "Webhook received from checkout.com. Payment captured on checkout.com hub";
            $order->addStatusToHistory(false, $message);
            $order->save();
        }

        $invoiceCollection = $order->getInvoiceCollection();
        foreach ($invoiceCollection as $invoice) {
            $invoiceIncrementId =  $invoice->getIncrementId();
        }

        // Send invoice email
        if (!null == $invoiceIncrementId) {
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
        $actionId = $webhookData->data->action_id;

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
            $parentTransactionId = Mage::getModel('ckopayment/checkoutcomCards')->getCkoLastTransId('Refund', $webhookData->data->id);

            $totalRefunded = Mage::getModel('ckopayment/checkoutcomCards')->getTotalRefunded($webhookData->data->id);
            
            $payment->setTransactionId($actionId);
            $payment->setParentTransactionId($parentTransactionId);
            $payment->setShouldCloseParentTransaction(true);

            $amountDecimal = Mage::getModel('ckopayment/checkoutcomUtils')
                    ->decimalToValue($amount, $currencyCode);
            $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
            $refundAmount = $currencySymbol.$amountDecimal;
            
            if ($amountLessThanGrandTotal) {
                $isClosed = $totalRefunded == $grandTotalsCents ? true : false;
                // set message in order history for partial refund and update status
                $message = "Webhook received from checkout.com. Registered notification about refunded amount of {$refundAmount}. Transaction ID: {$actionId}. Credit Memo has not been created. Please create offline Credit Memo.";

                $this->_addTransaction(
                    $payment,
                    $actionId,
                    'refund',
                    array('is_transaction_closed' => $isClosed,),
                    array('real_transaction_id' => $actionId),
                    false
                );

                $order->addStatusToHistory($status, $message);

            } else {

                if ($createCreditMemo) {
                    $payment->registerRefundNotification($order->getBaseGrandTotal());
                    $order->setPaymentIsRefunded(1);
                } else {
                    $message = "Webhook received from checkout.com. Registered notification about refunded amount of {$refundAmount}. Transaction ID: {$actionId}. Credit Memo has not been created. Please create offline Credit Memo.";
                    $order->addStatusToHistory($refundStatus, $message);
                }
            }
            
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
     * _addTransaction
     *
     * @param  mixed $payment
     * @param  mixed $transactionId
     * @param  mixed $transactionType
     * @return void
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

        $transaction = $payment->addTransaction($transactionType, null, false, $message);

        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }

        $payment->unsLastTransId();
        $transaction->setMessage($message);

        return $transaction;
    }
}