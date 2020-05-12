<?php

namespace Smart2Pay\GlobalPay\Model\System\Config\Backend;

/**
 * Backend model for merchant country. Default country used instead of empty value.
 */
class ConfiguredMethods extends \Magento\Framework\App\Config\Value
{
    const ERR_SURCHARGE_PERCENT = 1, ERR_SURCHARGE_SAVE = 2;

    // Array created in _beforeSave() and commited to database in _afterSave()
    // $_methods_to_save[{method_ids}][{country_ids}]['surcharge'],
    // $_methods_to_save[{method_ids}][{country_ids}]['fixed_amount'], ...
    protected $_methods_to_save = [];

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

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    protected $for_environment = false;

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
        \Smart2Pay\GlobalPay\Model\MethodFactory $methodFactory,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_methodFactory = $methodFactory;
        $this->_configuredMethodsFactory = $configuredMethodsFactory;
        $this->_s2pHelper = $s2pHelper;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Processing object after load data
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        return parent::_afterLoad();
    }

    public function beforeSave()
    {
        $sdk_helper = $this->_s2pHelper->getSDKHelper();

        $this->_methods_to_save = false;

        if (($groups_arr = $this->_s2pHelper->getParam('groups', false))
        && is_array($groups_arr)
        && !empty($groups_arr['smart2pay']) && is_array($groups_arr['smart2pay'])
        && !empty($groups_arr['smart2pay']['groups']) && is_array($groups_arr['smart2pay']['groups'])
        && !empty($groups_arr['smart2pay']['groups']['smart2pay_methods'])
        && is_array($groups_arr['smart2pay']['groups']['smart2pay_methods'])
        && !empty($groups_arr['smart2pay']['groups']['smart2pay_methods']['fields'])
        && is_array($groups_arr['smart2pay']['groups']['smart2pay_methods']['fields'])
        && !empty($groups_arr['smart2pay']['groups']['smart2pay_methods']['fields']['configured_methods'])
        && is_array($groups_arr['smart2pay']['groups']['smart2pay_methods']['fields']['configured_methods'])
        && !empty($groups_arr['smart2pay']['groups']['smart2pay_methods']['fields']['configured_methods']['action'])) {
            if ($groups_arr['smart2pay']['groups']['smart2pay_methods']['fields']['configured_methods']['action']
                === 'do_sync') {
                if (!$sdk_helper->refreshAvailableMethods()) {
                    $this->_dataSaveAllowed = false;
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Error while importing payment methods from server: %1.', $sdk_helper->getError())
                    );
                }
            }

            return $this;
        }

        $methods_obj = $this->_methodFactory->create();

        if (empty($this->for_environment)) {
            $this->for_environment = $this->_s2pHelper->getEnvironment();
        }

        if (!($form_s2p_enabled_methods = $this->_s2pHelper->getParam('s2p_enabled_methods', []))
         || !is_array($form_s2p_enabled_methods)) {
            $form_s2p_enabled_methods = [];
        }
        if (!($form_s2p_surcharge = $this->_s2pHelper->getParam('s2p_surcharge', []))
         || !is_array($form_s2p_surcharge)) {
            $form_s2p_surcharge = [];
        }
        if (!($form_s2p_fixed_amount = $this->_s2pHelper->getParam('s2p_fixed_amount', []))
         || !is_array($form_s2p_fixed_amount)) {
            $form_s2p_fixed_amount = [];
        }

        $existing_methods_params_arr = [];
        $existing_methods_params_arr['method_ids'] = $form_s2p_enabled_methods;
        $existing_methods_params_arr['include_countries'] = false;

        if (!($db_existing_methods = $methods_obj->getAllActiveMethods(
            $this->for_environment,
            $existing_methods_params_arr
        ))) {
            $db_existing_methods = [];
        }

        $this->_methods_to_save = [];
        foreach ($db_existing_methods as $method_id => $method_details) {
            if (empty($form_s2p_surcharge[$method_id])) {
                $form_s2p_surcharge[$method_id] = 0;
            }
            if (empty($form_s2p_fixed_amount[$method_id])) {
                $form_s2p_fixed_amount[$method_id] = 0;
            }

            if (!is_numeric($form_s2p_surcharge[$method_id])) {
                $this->_dataSaveAllowed = false;
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please provide a valid percent for method %1.', $method_details['display_name'])
                );
            }

            if (!is_numeric($form_s2p_fixed_amount[$method_id])) {
                $this->_dataSaveAllowed = false;
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please provide a valid fixed amount for method %1.', $method_details['display_name'])
                );
            }

            if (empty($this->_methods_to_save[$method_id])) {
                $this->_methods_to_save[$method_id] = [];
            }

            // TODO: add country ids instead of only 0 (all countries)
            $this->_methods_to_save[$method_id][0]['surcharge'] = $form_s2p_surcharge[$method_id];
            $this->_methods_to_save[$method_id][0]['fixed_amount'] = $form_s2p_fixed_amount[$method_id];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}. This specific method will not invalidate config cache as we don't use config caching
     * (we save in separate table)
     *
     * @return $this
     */
    public function afterSave()
    {
        if (!is_array($this->_methods_to_save)) {
            return $this;
        }

        if (empty($this->for_environment)) {
            $this->for_environment = $this->_s2pHelper->getEnvironment();
        }

        $configured_methods_obj = $this->_configuredMethodsFactory->create();

        if (($save_result = $configured_methods_obj->saveConfiguredMethods(
            $this->_methods_to_save,
            $this->for_environment
        )) !== true) {
            if (!is_array($save_result)) {
                $error_msg = __('Error saving methods to database. Please try again.');
            } else {
                $error_msg = implode('<br/>', $save_result);
            }

            throw new \Magento\Framework\Exception\LocalizedException($error_msg);
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
