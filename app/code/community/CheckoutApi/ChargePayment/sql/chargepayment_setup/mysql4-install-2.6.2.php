<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
CREATE TABLE `{$installer->getTable('chargepayment/customercard')}` (
   `entity_id` int(10) unsigned NOT NULL auto_increment,
   `customer_id` varchar(255) NOT NULL ,
   `card_id`  varchar(255) NOT NULL ,
   `bin` varchar(7) NOT NULL ,
   `card_number`  varchar(7) NOT NULL ,
   `save_card` varchar(1) NOT NULL,
   `card_type`  varchar(20) NOT NULL ,
    PRIMARY KEY  (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->getConnection()->addColumn($this->getTable('sales/quote_payment'), 'checkout_api_card_id', 'TEXT NULL');

$installer->getConnection()->addColumn($this->getTable('sales/order_payment'), 'checkout_api_card_id', 'TEXT NULL');

$installer->getConnection()->addColumn($this->getTable('sales/order'), 'charge_is_captured', 'INT NULL');

$installer->getConnection()->addColumn($this->getTable('sales/order'), 'charge_is_voided', 'INT NULL');

$installer->getConnection()->addColumn($this->getTable('sales/order'), 'charge_is_refunded', 'INT NULL');


$installer->endSetup();