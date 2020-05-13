<?php

/**
 * Class Checkoutcom_Ckopayment_Helper_Data
 */
class Checkoutcom_Ckopayment_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CODE_CHECKOUT_COM_CONFIG = 'checkoutcomconfig';
    const CODE_CHECKOUT_COM_CARDS = 'checkoutcomcards';
    const CODE_CHECKOUT_COM_APMS = 'checkoutcomapms';
    const CODE_CHECKOUT_COM_APPLEPAY = 'checkoutcomapplepay';
    const CODE_CHECKOUT_COM_GOOGLEPAY = 'checkoutcomgooglepay';
    const CODE_CHECKOUT_COM_WEBHOOK_LOG = 'checkoutcomwebhook.log';

    /**
     * Return field from config by payment method and store ID
     *
     * @param $method
     * @param $field
     * @param null $storeId
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getConfigData($method, $field, $storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore();
        }

        $path = "payment/{$method}/" . $field;

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Return Customer Email
     *
     * @param null $quoteId
     * @return string
     */
    public function getCustomerEmail($quoteId = null)
    {
        if (!empty($quoteId)) {
            $cart   = Mage::getModel('sales/quote')->load($quoteId);
            $email  = $cart->getBillingAddress()->getEmail();
            $email  = empty($email) ? $cart->getCustomerEmail() : $email;
        } else {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $email = $quote->getBillingAddress()->getEmail();
        }

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
     * Return current extension version
     *
     * @return string
     *
     */
    public function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getModuleConfig("Checkoutcom_Ckopayment")->version;
    }

    /**
     * Restore quote
     * Used to properly reload cart after failed 3D
     *
     * @param Mage_Sales_Model_Order $order
     * @return $this
     */
    public function restoreQuoteSession(Mage_Sales_Model_Order $order)
    {
        $quoteId    = $order->getQuoteId();
        $quote      = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote->getId()) {
            $quote->setIsActive(1)
                ->setReservedOrderId(null)
                ->save();

            Mage::getSingleton('checkout/session')->replaceQuote($quote);
        }

        return $this;
    }

    /**
     * Return true if is mada card
     *
     * @param $bin
     * @return bool
     * @throws Exception
     */
    public function isMadaCard($bin)
    {
        // Path to MADA_BIN.csv
        $csvPath = Mage::getModuleDir('Model', 'Checkoutcom_Ckopayment') . "/Model/Files/Mada/MADA_BINS.csv";

        $csv = new Varien_File_Csv();
        // Get the data from the file
        $csvData = $csv->getData($csvPath);

        // Remove the first row of csv columns
        unset($csvData[0]);

        // Build the MADA BIN array
        $binArray = [];
        foreach ($csvData as $row) {
            $binArray[] = $row[1];
        }

        return in_array($bin, $binArray);
    }
}
