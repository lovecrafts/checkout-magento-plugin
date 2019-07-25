<?php

class Checkoutcom_Ckopayment_Model_CustomerCard extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('ckopayment/customercard');
    }

    /**
     * Return list of saved cards for customer
     *
     * @param $customerId
     * @return object
     */
    public function getCustomerCardList($customerId)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId);

        return $collection;
    }

    /**
     * Return encrypted value.
     *
     * @param $entityId
     * @param $cardNumber
     * @param $cardType
     * @return string
     */
    public function getCardSecret($entityId, $cardNumber, $cardType)
    {
        return md5($entityId . '_' . $cardNumber . '_' . $cardType);
    }

    /**
     * Save source id in db
     *
     * @param Varien_Object $payment
     * @param $response
     * @param $isSaveCardCheck
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function saveCard($response, $isSaveCardCheck, $customerId)
    {
        $source = $response->source;
        $sourceId = $source['id'];
        $last4 = $source['last4'];
        $scheme = $source['scheme'];
        $saveCard = $isSaveCardCheck == true ? 1 : 0;
        $result = false;

        if (empty($customerId) || empty($last4) || empty($sourceId) || empty($scheme)) {
            $result = false;
        }

        //check if source id has already been saved in db
        if ($this->_isSourceAlreadyAdded($customerId, $sourceId, $scheme)) {

            $result = false;

        } elseif ($this->_isCardIdAlreadyAdded($customerId, $scheme, $last4)) {
            $entityId = $this->_getEntityId($customerId, $last4);

            try {

                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $table = $resource->getTableName('ckopayment_cards');
                $query = "UPDATE {$table} SET source_id = '{$sourceId}' WHERE entity_id = '{$entityId}'";
                $writeConnection->query($query);

                $result = true;

            } catch (Exception $e) {
                Mage::throwException(Mage::helper('ckopayment')->__('Cannot update Customer Data.'));
            }

        } else {

            try {
                $this->setCustomerId($customerId);
                $this->setSourceId($sourceId);
                $this->setLastFour($last4);
                $this->setCardScheme($scheme);
                $this->setSaveCard($saveCard);

                $this->save();

                $result = true;
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('ckopayment')->__('Cannot save Customer Data.'));
            }

        }

        return $result;
    }

    /**
     * @param $customerId
     * @param $sourceId
     * @param $scheme
     * @return bool
     */
    private function _isSourceAlreadyAdded($customerId, $sourceId, $scheme)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('source_id', $sourceId);
        $collection->addFieldToFilter('card_scheme', $scheme);

        return $collection->count() ? true : false;
    }

    /** Check if cardId exist in db
     * @param $customerId
     * @param $scheme
     * @param $last4
     * @return bool
     */
    private function _isCardIdAlreadyAdded($customerId, $scheme, $last4)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('card_scheme', $scheme);
        $collection->addFieldToFilter('last_four', $last4);

        return $collection->count() ? true : false;
    }

    /** Return entity id from db
     * @param $customerId
     * @param $last4
     * @return mixed
     */
    private function _getEntityId($customerId, $last4)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('ckopayment_cards');
        $query = "SELECT entity_id FROM {$table} WHERE customer_id = '{$customerId}' AND last_four = '{$last4}'";
        $result = $readConnection->fetchAll($query);
        $response = $result[0];

        return $response['entity_id'];
    }

    /**
     * Check if customer card exist
     *
     * @param $entityId
     * @param $customerId
     * @return array
     */
    public function customerCardExists($entityId, $customerId)
    {
        $result     = array();
        $collection = $this->getCustomerCardList($customerId);

        if (!$collection->count()) {
            return $result;
        }

        foreach ($collection as $entity) {
            $secret = $entity->getId();

            if ($entityId === $secret) {
                $result = $entity;
                break;
            }
        }

        return $result;
    }

    /**
     * Delete customer's source id from magento table 'ckopayment_cards'
     * @param $entityId
     * @throws Mage_Core_Exception
     */
    public function removeCard($entityId)
    {
        if (empty($entityId)) {
            Mage::throwException('Unable to delete empty Card.');
        }

        try {
            $this->load($entityId);
            $this->delete();
        } catch (Exception $e) {
            Mage::throwException('Unable to delete Card.');
        }
    }
}
