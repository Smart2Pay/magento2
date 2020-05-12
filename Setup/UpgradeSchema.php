<?php

namespace Smart2Pay\GlobalPay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\TestFramework\Event\Magento;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $current_version = $context->getVersion();

        if (version_compare($current_version, '2.1.0') < 0) {
            $this->upgradePluginTo210($setup, $context);
        }

        if (version_compare($current_version, '3.0.0') < 0) {
            $this->upgradePluginTo300($setup, $context);
        }

        if (version_compare($current_version, '3.0.1') < 0) {
            $this->upgradePluginTo301($setup, $context);
        }

        $setup->endSetup();
    }

    //
    // region code to upgrade to 2.1.0
    //
    private function upgradePluginTo210(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //
        //  Methods table
        //
        $methods_table = $setup->getTable('s2p_gp_methods');

        $setup->getConnection()->dropTable($methods_table);

        /**
         * Create table 's2p_gp_methods'
         */
        $table = $setup->getConnection()->newTable(
            $methods_table
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
            $setup->getIdxName('s2p_gp_methods', ['method_id']),
            ['method_id']
        )->addIndex(
            $setup->getIdxName('s2p_gp_methods', ['environment']),
            ['environment']
        )->addIndex(
            $setup->getIdxName('s2p_gp_methods', ['active']),
            ['active']
        )->setComment(
            'Smart2Pay Payment Methods'
        );
        $setup->getConnection()->createTable($table);

        //
        //  Configured methods table
        //
        $methods_configured_table = $setup->getTable('s2p_gp_methods_configured');

        $setup->getConnection()->addColumn(
            $methods_configured_table,
            'environment',
            [
                'after' => 'id',
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'default' => null,
                'comment' => 'Payment method environment',
            ]
        );

        $setup->getConnection()->addIndex(
            $methods_configured_table,
            $setup->getIdxName('s2p_gp_methods_configured', ['environment']),
            ['environment']
        );

        //
        //  Configured methods table
        //
        $countries_methods_table = $setup->getTable('s2p_gp_countries_methods');

        $setup->getConnection()->addColumn(
            $countries_methods_table,
            'environment',
            [
                'after' => 'id',
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'default' => null,
                'comment' => 'Payment method environment',
            ]
        );

        $setup->getConnection()->addIndex(
            $countries_methods_table,
            $setup->getIdxName('s2p_gp_countries_methods', ['environment']),
            ['environment']
        );

        //
        //  logs table
        //
        $logs_table = $setup->getTable('s2p_gp_logs');

        $setup->getConnection()->addColumn(
            $logs_table,
            'transaction_id',
            [
                'after' => 'log_type',
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => null,
                'comment' => 'Transaction ID',
            ]
        );

        $setup->getConnection()->addIndex(
            $logs_table,
            $setup->getIdxName('s2p_gp_countries_methods', ['transaction_id']),
            ['transaction_id']
        );
    }
    //
    // endregion
    //

    //
    // region code to upgrade to 3.0.0
    //
    private function upgradePluginTo300(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //
        //  logs table
        //
        $logs_table = $setup->getTable('s2p_gp_logs');

        $setup->getConnection()->changeColumn(
            $logs_table,
            'transaction_id',
            'transaction_id',
            [
                'after' => 'log_type',
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 100,
                'nullable' => true,
                'default' => null,
                'comment' => 'Transaction ID',
            ]
        );
    }
    //
    // endregion
    //

    //
    // region code to upgrade to 3.0.1
    //
    private function upgradePluginTo301(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //
        //  transactions table
        //
        $transactions_table = $setup->getTable('s2p_gp_transactions');

        $setup->getConnection()->addColumn(
            $transactions_table,
            '3dsecure',
            [
                'after' => 'extra_data',
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => 0,
                'comment' => 'Was this a 3DSecure transaction',
            ]
        );
    }
    //
    // endregion
    //

    //
    // region code to upgrade to a future version
    //
    private function upgradePluginToFutureVersion(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //
        //  Surcharge
        //
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_address'),
            's2p_surcharge_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_address'),
            's2p_surcharge_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_address'),
            's2p_surcharge_percent',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge percent'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_address'),
            's2p_surcharge_fixed_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge fixed amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_address'),
            's2p_surcharge_fixed_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base fixed amount'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            's2p_surcharge_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            's2p_surcharge_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            's2p_surcharge_percent',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge percent'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            's2p_surcharge_fixed_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge fixed amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            's2p_surcharge_fixed_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base fixed amount'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_percent',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge percent'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_fixed_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge fixed amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_fixed_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base fixed amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_amount_invoiced',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge amount invoiced'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            's2p_surcharge_base_amount_invoiced',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base amount invoiced'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_invoice'),
            's2p_surcharge_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_invoice'),
            's2p_surcharge_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0, 'length' => '(10,2)', 'comment' => 'Surcharge base amount' ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_invoice'),
            's2p_surcharge_fixed_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge fixed amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_invoice'),
            's2p_surcharge_fixed_base_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'default' => 0,
                'length' => '(10,2)',
                'comment' => 'Surcharge base fixed amount'
            ]
        );
    }
    //
    // endregion
    //
}
