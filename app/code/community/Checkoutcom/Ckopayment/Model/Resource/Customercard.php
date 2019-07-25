<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Resource_Customercard
 */
class Checkoutcom_Ckopayment_Model_Resource_Customercard extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('ckopayment/customercard', 'entity_id');
    }
}
