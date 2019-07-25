<?php

/**
 * Class Checkoutcom_Ckopayment_Model_Resource_Customercard_Collection
 */
class Checkoutcom_Ckopayment_Model_Resource_Customercard_Collection extends
    Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('ckopayment/customercard');
    }
}
