<?php

namespace Smart2Pay\GlobalPay\Model\System\Config\Backend;

/**
 * Backend model for merchant country. Default country used instead of empty value.
 */
class ConfiguredMethods extends \Magento\Framework\App\Config\Value
{
    const ERR_SURCHARGE_PERCENT = 1, ERR_SURCHARGE_SAVE = 2;

    // Array created in _beforeSave() and commited to database in _afterSave()
    // $_methods_to_save[{method_ids}][{country_ids}]['surcharge'], $_methods_to_save[{method_ids}][{country_ids}]['fixed_amount'], ...
    protected $_methods_to_save = array();

    /**
     * Data changes flag (true after setData|unsetData call)
     * @var $_hasDataChange bool
     */
    protected $_hasDataChanges = true;

    /**
     * Configured Methods Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory
     */
    private $_configuredMethodsFactory;

    /**
     * Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\MethodFactory
     */
    private $_methodFactory;

    /**
     * Helper
     *
     * @var \Smart2Pay\GlobalPay\Helper\Smart2Pay
     */
    protected $_helper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Smart2Pay\GlobalPay\Model\MethodFactory $methodFactory,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory,
        \Smart2Pay\GlobalPay\Helper\Smart2Pay $helperSmart2Pay,
        array $data = []
    ) {
        $this->_methodFactory = $methodFactory;
        $this->_configuredMethodsFactory = $configuredMethodsFactory;

        $this->_helper = $helperSmart2Pay;

        parent::__construct( $context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data );
    }

    /**
     * Processing object after load data
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        // echo 'ulicica';
        return parent::_afterLoad();
    }

    public function beforeSave()
    {
        $methods_obj = $this->_methodFactory->create();

        if( !($form_s2p_enabled_methods = $this->_helper->getParam( 's2p_enabled_methods', array() ))
            or !is_array( $form_s2p_enabled_methods ) )
            $form_s2p_enabled_methods = array();
        if( !($form_s2p_surcharge = $this->_helper->getParam( 's2p_surcharge', array() ))
            or !is_array( $form_s2p_surcharge ) )
            $form_s2p_surcharge = array();
        if( !($form_s2p_fixed_amount = $this->_helper->getParam( 's2p_fixed_amount', array() ))
            or !is_array( $form_s2p_fixed_amount ) )
            $form_s2p_fixed_amount = array();

        $existing_methods_params_arr = array();
        $existing_methods_params_arr['method_ids'] = $form_s2p_enabled_methods;
        $existing_methods_params_arr['include_countries'] = false;

        if( !($db_existing_methods = $methods_obj->getAllActiveMethods( $existing_methods_params_arr )) )
            $db_existing_methods = array();

        $this->_methods_to_save = array();
        foreach( $db_existing_methods as $method_id => $method_details )
        {
            if( empty( $form_s2p_surcharge[$method_id] ) )
                $form_s2p_surcharge[$method_id] = 0;
            if( empty( $form_s2p_fixed_amount[$method_id] ) )
                $form_s2p_fixed_amount[$method_id] = 0;

            if( !is_numeric( $form_s2p_surcharge[$method_id] ) )
            {
                $this->_dataSaveAllowed = false;
                throw new \Magento\Framework\Exception\LocalizedException( __( 'Please provide a valid percent for method %1.', $method_details['display_name'] ) );
            }

            if( !is_numeric( $form_s2p_fixed_amount[$method_id] ) )
            {
                $this->_dataSaveAllowed = false;
                throw new \Magento\Framework\Exception\LocalizedException( __( 'Please provide a valid fixed amount for method %1.', $method_details['display_name'] ) );
            }

            if( empty( $this->_methods_to_save[$method_id] ) )
                $this->_methods_to_save[$method_id] = array();

            // TODO: add country ids instead of only 0 (all countries)
            $this->_methods_to_save[$method_id][0]['surcharge'] = $form_s2p_surcharge[$method_id];
            $this->_methods_to_save[$method_id][0]['fixed_amount'] = $form_s2p_fixed_amount[$method_id];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}. This specific method will not invalidate config cache as we don't use config caching (we save in separate table)
     *
     * @return $this
     */
    public function afterSave()
    {
        if( !is_array( $this->_methods_to_save ) )
            return $this;

        $configured_methods_obj = $this->_configuredMethodsFactory->create();

        if( ($save_result = $configured_methods_obj->saveConfiguredMethods( $this->_methods_to_save )) !== true )
        {
            if( !is_array( $save_result ) )
                $error_msg = __( 'Error saving methods to database. Please try again.' );
            else
                $error_msg = implode( '<br/>', $save_result );

            throw new \Magento\Framework\Exception\LocalizedException( $error_msg );
        }

        return $this;
    }

    public function isValueChanged()
    {
        return true;
    }

    public function hasDataChanges()
    {
        return true;
    }
}
