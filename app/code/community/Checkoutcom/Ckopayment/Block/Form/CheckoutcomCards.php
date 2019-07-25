<?php

/**
 * Class Checkoutcom_Ckopayment_Block_Form_CheckoutcomCards
 */
class Checkoutcom_Ckopayment_Block_Form_CheckoutcomCards extends Mage_Payment_Block_Form_Cc
{
    const CONFIG = 'ckopayment/checkoutcomConfig';
    const CARDCONFIG = 'ckopayment/checkoutcomCards';
    const TEMPLATE = 'checkoutcom/form/checkoutcomframes.phtml';

    /**
     * Set template for checkout page
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate(self::TEMPLATE);
    }

    /**
     * Get Magento model for the card config
     *
     * @return mixed
     */
    protected function _getCardModel()
    {
        return Mage::getModel(self::CARDCONFIG);
    }

    /**
     * Get Public Key model
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return Mage::getModel(self::CONFIG)->getPublicKey();
    }

    /**
     * Get if the saved cards option is enabled  in the config
     *
     * @return mixed
     */
    public function getIsSaveCardEnable()
    {
        return $this->_getCardModel()->getIsSaveCardEnable();
    }

    /**
     * Get if the saved cards option is enabled  in the config
     *
     * @return mixed
     */
    public function getSavedCardOption()
    {
        return Mage::getModel(self::CONFIG)->getSavedCardOption();
    }

    /**
     * Get the saved cards option title
     *
     * @return mixed
     */
    public function getSaveCardTitle()
    {
        return $this->_getCardModel()->getSaveCardTitle();
    }

    public function getIsCvvRequire()
    {
        return $this->_getCardModel()->getIsCvvRequire();
    }

    /**
     * Check if the customer is logged in the current session
     *
     * @return mixed
     */
    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * Return array with customer card list
     *
     * @return array
     */
    public function getCustomerCardList()
    {
        $result = array();
        $customerId = $this->_getCardModel()->getCustomerId();

        if (empty($customerId)) {
            return $result;
        }

        $customerCardModel = Mage::getModel('ckopayment/customerCard');
        $cardCollection = $customerCardModel->getCustomerCardList($customerId);

        if (!$cardCollection->count()) {
            return $result;
        }

        foreach ($cardCollection as $index => $card) {
            if ($card->getSaveCard() == '') {
                continue;
            }

            $result[$index]['title'] = sprintf('%s', $card->getLastFour());
            $result[$index]['value'] = $card->getId();
            $result[$index]['type'] = strtolower($card->getCardScheme());
            $result[$index]['isMada'] = $card->getIsMada();
        }

        return $result;
    }

    /**
     * Return customer name if logged in.
     *
     * @return mixed
     */
    public function getCustomerName()
    {
        return Mage::getSingleton('customer/session')->getCustomer()->getName();
    }
}
