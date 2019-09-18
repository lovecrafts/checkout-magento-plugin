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

            // Check if not partial refund
            if (!$amountLessThanGrandTotal) {
                // check if can create invoice automatically
                if ($createInvoice) {
                    $message = "Webhook received from checkout.com. Payment captured on checkout.com hub";

                    try {
                        $payment = $order->getPayment();
                        $payment->setTransactionId($webhookData->data->action_id)
                            ->setIsTransactionClosed(0)
                            ->setCurrencyCode($order->getBaseCurrencyCode())
                            ->registerCaptureNotification($order->getBaseGrandTotal(), true);

                        $order->setPaymentIsCaptured(1);
                        $order->addStatusToHistory($captureStatus, $message);
                        $order->save();
                    } catch (Exception $e) {
                        Mage::log($e, Zend_Log::DEBUG, 'checkoutcom_webhook.log', true);
                        return false;
                    }
                } else {
                    $message = "Webhook received from checkout.com. Payment captured checkout.com hub";
                    $order->addStatusToHistory($captureStatus, $message);
                    $order->save();
                }
            } else {
                $amountDecimal = Mage::getModel('ckopayment/checkoutcomUtils')
                    ->decimalToValue($amount, $currencyCode);
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();

                // set message in order history for partial capture and update status
                $message = "Webhook received from checkout.com. An amount of {$currencySymbol}{$amountDecimal} has been partially captured on hub. No invoice created";
                $order->addStatusToHistory($captureStatus, $message);
                $order->save();
            }
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
            $captureStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getCapturedOrderStatus();
            $refundStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getRefundedOrderStatus();
            $createCreditMemo = Mage::getModel('ckopayment/checkoutcomConfig')->getCreateMemo();
            $amount = $webhookData->data->amount;
            $currencyCode = $order->getOrderCurrencyCode();
            $grandTotals = $order->getGrandTotal();
            $grandTotalsCents = Mage::getModel('ckopayment/checkoutcomUtils')
                ->valueToDecimal($grandTotals, $currencyCode);

            $amountLessThanGrandTotal = $amount < $grandTotalsCents ? true : false;

            // Check if not partial refund
            if (!$amountLessThanGrandTotal) {
                // check if can create invoice automatically
                if ($createCreditMemo) {
                    $message = "Webhook received from checkout.com. Payment refunded on checkout.com hub.";

                    try {
                        $service = Mage::getModel('sales/service_order', $order);
                        $transactionModel = Mage::getModel('sales/order_payment_transaction');
                        $transactionModel
                            ->setOrderPaymentObject($order->getPayment())
                            ->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND)
                            ->save();

                        $creditMemo = $service->prepareCreditmemo()
                            ->setPaymentRefundDisallowed(true)
                            ->setAutomaticallyCreated(true)
                            ->register()
                            ->setTransactionId($webhookData->data->action_id)
                            ->save();

                        $order->setPaymentIsRefunded(1);
                        $order->addStatusToHistory($refundStatus, $message);
                        $order->save();
                    } catch (Exception $e) {
                        Mage::log($e, Zend_Log::DEBUG, 'checkoutcom_webhook.log', true);
                        return false;
                    }
                } else {
                    $message = "Webhook received from checkout.com. Payment refunded on checkout.com hub.";
                    $order->addStatusToHistory($refundStatus, $message);
                    $order->save();
                }
            } else {
                $amountDecimal = Mage::getModel('ckopayment/checkoutcomUtils')
                    ->decimalToValue($amount, $currencyCode);
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();

                // set message in order history for partial capture and update status
                $message = "Webhook received from checkout.com. An amount of {$currencySymbol}{$amountDecimal} has been partially refunded on checkout.com hub. No credit memo created";
                $order->addStatusToHistory($captureStatus, $message);
                $order->save();
            }
        } else {
            $message = "Webhook received from checkout.com. Payment refunded successfully here";
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
}
