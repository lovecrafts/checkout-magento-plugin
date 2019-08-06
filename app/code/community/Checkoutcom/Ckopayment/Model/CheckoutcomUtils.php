<?php

/**
 * Class Checkoutcom_Ckopayment_Model_CheckoutcomUtils
 */
class Checkoutcom_Ckopayment_Model_CheckoutcomUtils
{
    /**
     * Format amount in cents
     *
     * @param $amount
     * @param $currencySymbol
     * @return float|int
     */
    public function valueToDecimal($amount, $currencySymbol)
    {
        $currency = strtoupper($currencySymbol);
        $threeDecimalCurrencyList = array('BHD', 'LYD', 'JOD', 'IQD', 'KWD', 'OMR', 'TND');
        $zeroDecimalCurencyList = array(
            'BYR',
            'XOF',
            'BIF',
            'XAF',
            'KMF',
            'XOF',
            'DJF',
            'XPF',
            'GNF',
            'JPY',
            'KRW',
            'PYG',
            'RWF',
            'VUV',
            'VND',
        );

        if (in_array($currency, $threeDecimalCurrencyList)) {
            $value = (int) ($amount * 1000);
        } elseif (in_array($currency, $zeroDecimalCurencyList)) {
            $value = floor($amount);
        } else {
            $value = round($amount * 100);
        }

        return $value;
    }

    /**
     * Format amount in decimal
     *
     * @param $amount
     * @param $currencySymbol
     * @return float|int
     */
    public function decimalToValue($amount, $currencySymbol)
    {
        $currency = strtoupper($currencySymbol);
        $threeDecimalCurrencyList = array('BHD', 'LYD', 'JOD', 'IQD', 'KWD', 'OMR', 'TND');
        $zeroDecimalCurencyList = array(
            'BYR',
            'XOF',
            'BIF',
            'XAF',
            'KMF',
            'XOF',
            'DJF',
            'XPF',
            'GNF',
            'JPY',
            'KRW',
            'PYG',
            'RWF',
            'VUV',
            'VND',
        );

        if (in_array($currency, $threeDecimalCurrencyList)) {
            $value = $amount / 1000;
        } elseif (in_array($currency, $zeroDecimalCurencyList)) {
            $value = $amount;
        } else {
            $value = $amount / 100;
        }

        return $value;
    }

    /**
     * Return Quote from session
     *
     * @param null $quoteId
     * @return mixed
     *
     */
    public function getQuote($quoteId = null)
    {
        $quoteId = (int) $quoteId;
        if (!empty($quoteId)) {
            return Mage::getModel('sales/quote')->load($quoteId);
        }
        return Mage::getSingleton('checkout/session')->getQuote();
    }
}
