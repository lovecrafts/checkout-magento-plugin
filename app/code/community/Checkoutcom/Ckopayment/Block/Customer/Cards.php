<?php

class Checkoutcom_Ckopayment_Block_Customer_Cards extends Mage_Core_Block_Template
{
    /**
     * Return array with customer card list
     *
     * @return array
     */
    public function getCustomerCardList()
    {
        $result = array();
        $customerId = Mage::getModel('ckopayment/checkoutcomCards')->getCustomerId();

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

            $result[$index]['title'] = sprintf('•••• %s', $card->getLastFour());
            $result[$index]['value'] = $card->getId(); //$customerCardModel->getCardSecret($card->getId(), $card->getLastFour(), $card->getCardScheme());
            $result[$index]['type'] = $card->getCardScheme();
        }

        return $result;
    }
}
