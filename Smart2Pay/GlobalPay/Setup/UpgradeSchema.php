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
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;

        $installer->startSetup();

        $current_version = $context->getVersion();

        if( !$current_version )
        {
            // Just installed (no previous version existed)
        }

        //
        // code to upgrade to 2.1.0
        //
        if( version_compare( $current_version, '2.1.0' ) < 0 )
        {
            //
            //  Methods table
            //
            $methods_table = $installer->getTable( 's2p_gp_methods' );

            $installer->getConnection()->dropTable( $methods_table );

            /**
             * Create table 's2p_gp_methods'
             */
            $table = $installer->getConnection()->newTable(
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
            $installer->getConnection()->createTable( $table );

            //
            //  Configured methods table
            //
            $methods_configured_table = $installer->getTable( 's2p_gp_methods_configured' );

            $installer->getConnection()->addColumn( $methods_configured_table, 'environment',
                [
                    'after' => 'id',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Payment method environment',
                ]
            );

            $installer->getConnection()->addIndex( $methods_configured_table,
                                                   $installer->getIdxName( 's2p_gp_methods_configured', ['environment'] ),
                                                   ['environment'] );

            //
            //  Configured methods table
            //
            $countries_methods_table = $installer->getTable( 's2p_gp_countries_methods' );

            $installer->getConnection()->addColumn( $countries_methods_table, 'environment',
                [
                    'after' => 'id',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Payment method environment',
                ]
            );

            $installer->getConnection()->addIndex( $countries_methods_table,
                                                   $installer->getIdxName( 's2p_gp_countries_methods', ['environment'] ),
                                                   ['environment'] );

            //
            //  logs table
            //
            $logs_table = $installer->getTable( 's2p_gp_logs' );

            $installer->getConnection()->addColumn( $logs_table, 'transaction_id',
                [
                    'after' => 'log_type',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Transaction ID',
                ]
            );

            $installer->getConnection()->addIndex( $logs_table,
                                                   $installer->getIdxName( 's2p_gp_countries_methods', ['transaction_id'] ),
                                                   ['transaction_id'] );
        }
        //
        // END code to upgrade to 2.1.0
        //

        $installer->endSetup();
    }
}
