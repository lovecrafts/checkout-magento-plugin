<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Localisation
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Localisation
{
    /**
     * Decorate select in System Configuration
     *
     * @return array
     *
     * @version
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'EN-GB',
                'label' => Mage::helper('chargepayment')->__('English')
            ),
            array(
                'value' => 'NL-NL',
                'label' => Mage::helper('chargepayment')->__('Dutch')
            ),
            array(
                'value' => 'FR-FR',
                'label' => Mage::helper('chargepayment')->__('French')
            ),
            array(
                'value' => 'DE-DE',
                'label' => Mage::helper('chargepayment')->__('German')
            ),
            array(
                'value' => 'IT-IT',
                'label' => Mage::helper('chargepayment')->__('Italian')
            ),
            array(
                'value' => 'KR-KR',
                'label' => Mage::helper('chargepayment')->__('Korean')
            ),
            array(
                'value' => 'ES-ES',
                'label' => Mage::helper('chargepayment')->__('Spanish')
            ),
        );



    }
}