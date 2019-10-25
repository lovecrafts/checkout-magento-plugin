<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$this->startSetup();

/**
 * can't do this!
 */
// add column charge_is_captured
$this->getConnection()->addColumn(
    $this->getTable('sales/order'),
    'payment_is_captured',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'default'   => 0,
        'comment'   => 'Payment is captured by Checkout.com API',
    )
);


/**
 * can't do this!
 */
// add column charge_is_voided
$this->getConnection()->addColumn(
    $this->getTable('sales/order'),
    'payment_is_voided',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'default'   => 0,
        'comment'   => 'Payment is voided by Checkout.com API',
    )
);


/**
 * can't do this!
 */
// add column charge_is_refunded
$this->getConnection()->addColumn(
    $this->getTable('sales/order'),
    'payment_is_refunded',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'default'   => 0,
        'comment'   => 'Payment is refunded by Checkout.com API',
    )
);


// Check if table ckopayment_cards exist
if ($this->getConnection()->isTableExists($this->getTable('ckopayment/customercard')) != true) {
    // Create table ckopayment_cards for saved card functionality
    $table = $this->getConnection()
        ->newTable($this->getTable('ckopayment/customercard'))
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'ID')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
        ), 'Customer ID from Magento')
        ->addColumn('source_id', Varien_Db_Ddl_Table::TYPE_TEXT, '100', array(
            'nullable' => false,
        ), 'Source ID from Checkout.com')
        ->addColumn('last_four', Varien_Db_Ddl_Table::TYPE_CHAR, '4', array(
            'nullable' => false,
        ), 'Last for number in customer card number')
        ->addColumn('card_scheme', Varien_Db_Ddl_Table::TYPE_TEXT, '20', array(
            'nullable' => false,
        ), 'Credit Card scheme')
        ->addColumn('save_card', Varien_Db_Ddl_Table::TYPE_TEXT, '1', array(
            'nullable' => false,
        ), 'Save card')
        ->addColumn('is_mada', Varien_Db_Ddl_Table::TYPE_TEXT, '1', array(
            'nullable' => true,
        ), 'Save card');

    $table->addIndex(
        $this->getIdxName(
            $this->getTable('ckopayment/customercard'),
            array(
                'customer_id',
                'source_id',
                'last_four',
                'card_scheme',
                'save_card',
                'is_mada',
            ),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array(
            'customer_id',
            'source_id',
            'last_four',
            'card_scheme',
            'save_card',
            'is_mada',
        ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    );

    $this->getConnection()->createTable($table);
}

// Check if table chargepayment_cards exist
// Migrate cardId to ckopayment_cards
if ($this->getConnection()->isTableExists($this->getTable('chargepayment_cards'))) {
    $this->run("INSERT  INTO {$this->getTable('ckopayment/customercard')} (
              `customer_id`,
              `source_id`,
              `last_four`,
              `card_scheme`,
              `save_card`
        )
        SELECT `customer_id`, `card_id`, `card_number`, `card_type`, `save_card`
        FROM {$this->getTable('chargepayment_cards')}
        WHERE `save_card` = 1
    ");
}

$this->endSetup();
