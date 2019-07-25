<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_Apms
 */
class Checkoutcom_Ckopayment_Model_Adminhtml_System_Config_Source_Apms
{
    /**
     * Decorate select in System Configuration
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'alipay',
                'label' => 'Alipay',
            ),
            array(
                'value' => 'boleto',
                'label' => 'Boleto',
            ),
            array(
                'value' => 'giropay',
                'label' => 'Giropay',
            ),
            array(
                'value' => 'ideal',
                'label' => 'iDEAL',
            ),
            array(
                'value' => 'klarna',
                'label' => 'Klarna',
            ),
            array(
                'value' => 'poli',
                'label' => 'Poli',
            ),
            array(
                'value' => 'sepa',
                'label' => 'SEPA Direct Debit',
            ),
            array(
                'value' => 'sofort',
                'label' => 'Sofort',
            ),
            array(
                'value' => 'eps',
                'label' => 'EPS',
            ),
            array(
                'value' => 'bancontact',
                'label' => 'Bancontact',
            ),
            array(
                'value' => 'knet',
                'label' => 'KNET',
            ),
            array(
                'value' => 'fawry',
                'label' => 'Fawry',
            )
        );
    }
}
