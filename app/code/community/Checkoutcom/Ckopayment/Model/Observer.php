<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Observer
 */
class Checkoutcom_Ckopayment_Model_Observer
{
    /**
     * Set order status depending on selection from Authorised order status from Checkout.com configuration
     *
     * @param Varien_Event_Observer $observer
     */
    public function setNewOrderStatus(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $authStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getAuthorisedOrderStatus();

        $payment = $order->getPayment();
        // check if fraud detected
        if ($payment->getIsFraudDetected()) {
            $order->setStatus(Mage_Sales_Model_Order::STATUS_FRAUD, true)->save();
        } elseif ($authStatus == 'pending' && !$payment->getAdditionalInformation('is3d')) {
            $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
        }
    }

    /**
     * Set order status depending on selection from Voided order status from Checkout.com configuration
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function setVoidOrderStatus(Varien_Event_Observer $observer)
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);

        $voidStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getVoidedOrderStatus();
        $authStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getAuthorisedOrderStatus();

        if ($voidStatus == 'canceled') {
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
        } else {
            if ($authStatus == 'pending') {
                $order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
            }
        }

        $order->save();

        return $this;
    }

    /**
     * Set order status depending on selection from Captured order status from Checkout.com configuration
     *
     * @return $this
     * @throws Exception
     */
    public function setCaptureOrderStatus()
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);

        $captureStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getCapturedOrderStatus();

        // Set order status selected from admin module setting;
        if (!$captureStatus == 'processing') {
            $order->addStatusToHistory($captureStatus, 'Payment captured successfully on checkout.com hub.');
            $order->save();
        }

        return $this;
    }

    /**
     * Set order status depending on selection from Refunded order status from Checkout.com configuration
     *
     * @return $this
     * @throws Exception
     */
    public function setRefundOrderStatus()
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $payment = $order->getPayment();

        $refundStatus = Mage::getModel('ckopayment/checkoutcomConfig')->getRefundedOrderStatus();

        // Set order status selected from admin module setting;
        if ($refundStatus == 'processing') {
            return $this;
        } elseif ($refundStatus == 'closed') {
            $payment->setIsTransactionClosed(0);
            $payment->save();
        } else {
            $order->addStatusToHistory($refundStatus, 'Payment refunded successfully on checkout.com hub.');
        }

        $order->save();

        return $this;
    }
}
