<?php

class Checkoutcom_Ckopayment_CardsController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function removeAction()
    {
        $entity_id = $this->getRequest()->getParam('card');

        if (empty($entity_id)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('ckopayment')->__("Unable to delete Card."));
            return $this->_redirect('ckopayment/cards');
        }

        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

        if (empty($customerId)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('ckopayment')->__("Session Expired."));
            return $this->_redirect('customer/account/login');
        }

        $cardModel = Mage::getModel('ckopayment/customerCard');
        $card = $cardModel->customerCardExists($entity_id, $customerId);

        if (empty($card)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('ckopayment')->__("Unable to delete Card."));
            return $this->_redirect('ckopayment/cards');
        }

        $cardModel->removeCard($card->getId());

        Mage::getSingleton('core/session')->addSuccess(Mage::helper('ckopayment')->__("Card was removed."));
        $this->_redirect('ckopayment/cards');
    }
}
