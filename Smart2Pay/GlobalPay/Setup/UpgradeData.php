<?php

namespace Smart2Pay\GlobalPay\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\TestFramework\Event\Magento;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;

        $installer->startSetup();

        $current_version = $context->getVersion();

        //
        // code to upgrade to 2.1.0
        //
        if( version_compare( $current_version, '2.1.0' ) < 0 )
        {
            //
            //  Methods table
            //
            $methods_configured_table = $installer->getTable( 's2p_gp_methods' );

            $installer->getConnection()->truncateTable( $methods_configured_table );

            //
            //  Configured methods table
            //
            $methods_configured_table = $installer->getTable( 's2p_gp_methods_configured' );

            $installer->getConnection()->truncateTable( $methods_configured_table );

            //
            //  Methods countries table
            //
            $countries_methods_table = $installer->getTable( 's2p_gp_countries_methods' );

            $installer->getConnection()->truncateTable( $countries_methods_table );
        }
        //
        // END code to upgrade to 2.1.0
        //

        $installer->endSetup();
    }
}
