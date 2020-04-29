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
        $setup->startSetup();

        $current_version = $context->getVersion();

        if( version_compare( $current_version, '2.1.0' ) < 0 )
            $this->_upgrade_2_1_0( $setup, $context );

        $setup->endSetup();
    }

    //
    //region code to upgrade to 2.1.0
    //
    private function _upgrade_2_1_0( ModuleDataSetupInterface $setup, ModuleContextInterface $context )
    {
        //
        //  Methods table
        //
        $methods_configured_table = $setup->getTable( 's2p_gp_methods' );

        $setup->getConnection()->truncateTable( $methods_configured_table );

        //
        //  Configured methods table
        //
        $methods_configured_table = $setup->getTable( 's2p_gp_methods_configured' );

        $setup->getConnection()->truncateTable( $methods_configured_table );

        //
        //  Methods countries table
        //
        $countries_methods_table = $setup->getTable( 's2p_gp_countries_methods' );

        $setup->getConnection()->truncateTable( $countries_methods_table );
    }
    //
    //endregion code to upgrade to 2.1.0
    //
}
