<?php

class Checkoutcom_Ckopayment_Model_Session_Quote extends Mage_Core_Model_Session_Abstract
{
    /**
     * Class constructor. Initialize session namespace
     */
    public function __construct()
    {
        $this->init('ckopayment_session');
    }
}
