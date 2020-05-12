<?php

namespace Smart2Pay\GlobalPay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\TestFramework\Event\Magento;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 's2p_gp_methods'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('s2p_gp_methods')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Primary key'
        )->addColumn(
            'method_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Payment Method Id'
        )->addColumn(
            'environment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Payment method environment'
        )->addColumn(
            'display_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Payment method display name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Payment method description'
        )->addColumn(
            'logo_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Logo image'
        )->addColumn(
            'guaranteed',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            2,
            ['default' => 0, 'nullable' => false],
            'Guaranteed?'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            2,
            ['default' => 0, 'nullable' => false],
            'Active'
        )->addIndex(
            $installer->getIdxName('s2p_gp_methods', ['method_id']),
            ['method_id']
        )->addIndex(
            $installer->getIdxName('s2p_gp_methods', ['environment']),
            ['environment']
        )->addIndex(
            $installer->getIdxName('s2p_gp_methods', ['active']),
            ['active']
        )->setComment(
            'Smart2Pay Payment Methods'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 's2p_gp_countries'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('s2p_gp_countries')
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Country ID'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3,
            ['nullable' => true],
            'Country code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Country name'
        )->addIndex(
            $installer->getIdxName('s2p_gp_countries', ['code']),
            ['code']
        )->setComment(
            'Smart2Pay Countries'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 's2p_gp_countries_methods'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('s2p_gp_countries_methods')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'environment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Payment method environment'
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            'Country ID'
        )->addColumn(
            'method_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            'Method ID'
        )->addColumn(
            'priority',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['default' => 0],
            'Method priority (display order)'
        )->addIndex(
            $installer->getIdxName('s2p_gp_countries_methods', ['environment']),
            ['environment']
        )->addIndex(
            $installer->getIdxName('s2p_gp_countries_methods', ['country_id']),
            ['country_id']
        )->addIndex(
            $installer->getIdxName('s2p_gp_countries_methods', ['method_id']),
            ['method_id']
        )->setComment(
            'Smart2Pay Methods per Countries'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 's2p_gp_transactions'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('s2p_gp_transactions')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'method_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            'Method ID'
        )->addColumn(
            'payment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            'Smart2Pay Transaction ID'
        )->addColumn(
            'merchant_transaction_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            150,
            ['nullable' => true],
            'Merchant order ID'
        )->addColumn(
            'site_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            'Smart2Pay Site ID'
        )->addColumn(
            'environment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            [ 'nullable' => true, 'default'=> 'live' ],
            'Environment of transaction'
        )->addColumn(
            'extra_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'Key-Value extra details for transaction'
        )->addColumn(
            'payment_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['default' => 0],
            'Status received from server'
        )->addColumn(
            'created',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['default' => 0],
            'Creation timestamp'
        )->addColumn(
            'updated',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['default' => 0],
            'Last update timestamp'
        )->addIndex(
            $installer->getIdxName('s2p_gp_transactions', ['merchant_transaction_id']),
            ['merchant_transaction_id']
        )->addIndex(
            $installer->getIdxName('s2p_gp_transactions', ['method_id']),
            ['method_id']
        )->addIndex(
            $installer->getIdxName('s2p_gp_transactions', ['payment_id']),
            ['payment_id']
        )->setComment(
            'Transaction details from backend script. Will be used in order details'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 's2p_gp_methods_configured'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('s2p_gp_methods_configured')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'environment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            [ 'nullable' => true, 'default'=> 'live' ],
            'Method environment'
        )->addColumn(
            'method_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            'Method ID'
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 0],
            '0 for all countries'
        )->addColumn(
            'surcharge',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            [6, 2],
            ['default' => 0],
            'Surcharge percent from total order amount to be used as payment fee'
        )->addColumn(
            'fixed_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            [6, 2],
            ['default' => 0],
            'Surcharge fixed amount to be used as payment fee'
        )->addIndex(
            $installer->getIdxName('s2p_gp_methods_configured', ['environment']),
            ['environment']
        )->addIndex(
            $installer->getIdxName('s2p_gp_methods_configured', ['country_id']),
            ['country_id']
        )->addIndex(
            $installer->getIdxName('s2p_gp_methods_configured', ['method_id']),
            ['method_id']
        )->setComment(
            'Payment methods to be used and their surcharge (if applicable)'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 's2p_gp_logs'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('s2p_gp_logs')
        )->addColumn(
            'log_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'log_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Log type'
        )->addColumn(
            'transaction_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Transaction ID'
        )->addColumn(
            'log_message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'Log message'
        )->addColumn(
            'log_source_file',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'File where log was triggered'
        )->addColumn(
            'log_source_file_line',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Line in file'
        )->addColumn(
            'log_created',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Log creation timestamp'
        )->addIndex(
            $installer->getIdxName('s2p_gp_logs', ['transaction_id']),
            ['transaction_id']
        )->addIndex(
            $installer->getIdxName('s2p_gp_logs', ['log_type']),
            ['log_type']
        )->setComment(
            'Smart2Pay Logs'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
