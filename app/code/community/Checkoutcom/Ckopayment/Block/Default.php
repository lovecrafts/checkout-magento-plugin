<?php

class Checkoutcom_Ckopayment_Block_Default extends Mage_Core_Block_Template
{
    public function getCustomerInfo()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $billingAddress = $quote->getBillingAddress();
        $billStreet = Mage::helper('customer/address')
            ->convertStreetLines($billingAddress->getStreet(), 2);

        $billCity = $billingAddress->getCity();
        $billCountry = $billingAddress->getCountry();

        $arr = array(
            'customerName' => $billingAddress['firstname'] . ' ' . $billingAddress['lastname'],
            'billAddress1' => $billStreet[0],
            'billAddress2' => $billStreet[1],
            'billCity' => $billCity,
            'billCountry'=> Mage::app()->getLocale()->getCountryTranslation($billCountry)
        );

       return $arr;
    }
}
