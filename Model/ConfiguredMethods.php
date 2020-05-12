<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface;
use Magento\Framework\DataObject\IdentityInterface;

class ConfiguredMethods extends \Magento\Framework\Model\AbstractModel
    implements ConfiguredMethodsInterface, IdentityInterface
{
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'smart2pay_globalpay_configuredmethod';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smart2pay_globalpay_configuredmethod';

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\CountryMethodFactory
     */
    private $_countryMethodFactory;

    /**
     * Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\MethodFactory
     */
    private $_methodFactory;

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\CountryFactory
     */
    private $_countryFactory;

    /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethodsFactory */
    private $_configuredMethodsFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Smart2Pay\GlobalPay\Model\CountryMethodFactory $countryMethodFactory,
        \Smart2Pay\GlobalPay\Model\CountryFactory $countryFactory,
        \Smart2Pay\GlobalPay\Model\MethodFactory $methodFactory,
        \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethodsFactory $cm_resource,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_countryMethodFactory = $countryMethodFactory;
        $this->_countryFactory = $countryFactory;
        $this->_methodFactory = $methodFactory;
        $this->_configuredMethodsFactory = $cm_resource;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods');
    }

    /**
     * Check if a method is configured for $method_id, country_id pair
     * return details if method is configured
     *
     * @param int $method_id
     * @param int $country_id
     * @param bool|string $environment
     * @return int
     */
    public function checkMethodCountryID($method_id, $country_id, $environment = false)
    {
        $cm_obj = $this->_configuredMethodsFactory->create();

        // if( $this->_resource )
        //     return $this->_resource->checkMethodCountryID( $method_id, $country_id, $environment );

        return $cm_obj->checkMethodCountryID($method_id, $country_id, $environment);
    }

    /**
     * Check if method_id key exists
     * return method id if method exists
     *
     * @param int $country_id
     * @param bool|string $environment
     * @return array
     */
    public function getMethodsForCountry($country_id, $environment = false)
    {
        $cm_obj = $this->_configuredMethodsFactory->create();

        // if( $this->_resource )
        //     return $this->_resource->getMethodsForCountry( $country_id, $environment );

        return $cm_obj->getMethodsForCountry($country_id, $environment);
    }

    /**
     * Check if method_id key exists
     * return method id if method exists
     *
     * @param int $method_id
     * @param bool|string $environment
     * @return array
     */
    public function getCountriesForMethod($method_id, $environment = false)
    {
        $cm_obj = $this->_configuredMethodsFactory->create();

        // if( $this->_resource )
        //     return $this->_resource->getCountriesForMethod( $method_id, $environment );

        return $cm_obj->getCountriesForMethod($method_id, $environment);
    }

    /**
     * @param string $environment
     * @param bool|array $params
     *
     * @return array
     */
    public function getAllConfiguredMethods($environment, $params = false)
    {
        if (empty($params) || !is_array($params)) {
            $params = [];
        }

        if (!isset($params['only_active'])) {
            $params['only_active'] = true;
        }

        // $return_arr[{method_ids}][{country_ids}]['surcharge'],
        // $return_arr[{method_ids}][{country_ids}]['fixed_amount'], ...
        $return_arr = [];

        $collection = $this->getCollection();

        $collection->addFieldToSelect('*');
        $collection->addFieldToFilter('main_table.environment', $environment);

        if (!empty($params['only_active'])) {
            $method_collection = $this->_methodFactory->create()->getCollection();

            $collection->getSelect()->join(
                $method_collection->getMainTable(),
                'main_table.method_id = '.$method_collection->getMainTable().'.method_id'
            );

            $collection->addFieldToFilter($method_collection->getMainTable().'.active', 1);
            $collection->addFieldToFilter($method_collection->getMainTable().'.environment', $environment);
        }

        while (($configured_method_obj = $collection->fetchItem())
               && ($configured_method_arr = $configured_method_obj->getData())) {
            if (empty($configured_method_arr['method_id'])) {
                continue;
            }

            $return_arr[$configured_method_arr['method_id']][$configured_method_arr['country_id']]
                = $configured_method_arr;

            if (!empty($return_arr[$configured_method_arr['method_id']]
                       [$configured_method_arr['country_id']]['display_name'])) {
                $return_arr[$configured_method_arr['method_id']][$configured_method_arr['country_id']]['display_name'] =
                    __(
                        $return_arr[$configured_method_arr['method_id']]
                        [$configured_method_arr['country_id']]['display_name']
                    )->render();
            }
            if (!empty($return_arr[$configured_method_arr['method_id']]
                       [$configured_method_arr['country_id']]['description'])) {
                $return_arr[$configured_method_arr['method_id']][$configured_method_arr['country_id']]['description'] =
                    __(
                        $return_arr[$configured_method_arr['method_id']]
                        [$configured_method_arr['country_id']]['description']
                    )->render();
            }
        }

        return $return_arr;
    }

    /**
     * @param int $method_id ID of method to get all configurations for
     * @param string $environment
     * @param bool|array $params
     *
     * @return bool|array $return_arr[{country_code}]['surcharge'], $return_arr[{country_ids}]['fixed_amount'], ...
     */
    public function getConfiguredMethodDetails($method_id, $environment, $params = false)
    {
        $method_id = (int)$method_id;
        if (empty($method_id)) {
            return false;
        }

        if (empty($params) || !is_array($params)) {
            $params = [];
        }

        if (!isset($params['only_active'])) {
            $params['only_active'] = true;
        }
        if (!isset($params['country_code'])) {
            $params['country_code'] = '';
        }

        $our_country_id = 0;
        if (!empty($params['country_code'])) {
            $params['country_code'] = strtoupper(trim($params['country_code']));

            if (strlen($params['country_code']) !== 2) {
                return false;
            }

            $c_collection = $this->_countryFactory->create()->getCollection();
            $c_collection->addFieldToSelect('*');
            $c_collection->addFieldToFilter('main_table.code', $params['country_code']);
            $c_collection->getSelect()->limit(1);

            if (($country_obj = $c_collection->fetchItem())
            && ($country_arr = $country_obj->getData())) {
                $our_country_id = $country_arr['country_id'];
            }

            if (empty($our_country_id)) {
                return false;
            }
        }

        $collection = $this->getCollection();

        $collection->addFieldToSelect('*');

        $collection->addFieldToFilter('main_table.method_id', $method_id);
        $collection->addFieldToFilter('main_table.environment', $environment);

        if (!empty($our_country_id)) {
            $collection->addFieldToFilter(
                [
                   'main_table.country_id',
                   'main_table.country_id'
                ],
                [ 0, $our_country_id ]
            );

            $collection->setOrder('main_table.country_id', 'DESC');
        }

        $method_collection = $this->_methodFactory->create()->getCollection();

        $collection->getSelect()->join(
            $method_collection->getMainTable(),
            'main_table.method_id = '.$method_collection->getMainTable().'.method_id'
        );

        if (!empty($params['only_active'])) {
            $collection->addFieldToFilter($method_collection->getMainTable().'.active', 1);
        }

        $collection->addFieldToFilter($method_collection->getMainTable().'.environment', $environment);

        $data_arr = [];
        $countries_ids_arr = [];
        while (($configured_method_obj = $collection->fetchItem())
               && ($configured_method_arr = $configured_method_obj->getData())) {
            if (!empty($configured_method_arr['country_id'])) {
                $countries_ids_arr[] = $configured_method_arr['country_id'];
            }

            $data_arr[$configured_method_arr['country_id']] = $configured_method_arr;
        }

        if (empty($data_arr)) {
            return false;
        }

        $ids_to_codes_arr = [ 0 => Country::INTERNATIONAL_CODE ];
        // if (!empty($countries_ids_arr)) {
        //     //! TODO: query countries table to obtain country codes from ids
        //     // ATM all method settings are same for all countries...
        // }

        $return_arr = [];
        foreach ($data_arr as $country_id => $details_arr) {
            if (!isset($ids_to_codes_arr[$country_id])) {
                continue;
            }

            $return_arr[$ids_to_codes_arr[$country_id]] = $details_arr;
        }

        return (!empty($return_arr)?$return_arr:false);
    }

    /**
     * @param string $environment
     * @param bool|array $params
     *
     * @return array
     */
    public function getAllConfiguredMethodsPerCountryCode($environment, $params = false)
    {
        if (empty($params) || !is_array($params)) {
            $params = [];
        }

        if (!($all_configured_methods = $this->getAllConfiguredMethods($environment, [ 'only_active' => true ]))) {
            return [];
        }

        $method_ids = array_keys($all_configured_methods);
        $countryMethodModel = $this->_countryMethodFactory->create();

        if (!($country_methods = $countryMethodModel->getCountriesForMethodsList($method_ids))) {
            return [];
        }

        $collection = $this->getCollection();

        $collection->addFieldToSelect('*');

        // $return_arr['countries'][{country_code}][{method_id}] = $method_data;
        // $return_arr['methods'][{method_id}] = $method_details;
        //
        // $method_data['method_id'], $method_data['surcharge'], $method_data['base_amount'],
        // $method_data['display_name'], $method_data['description'], $method_data['logo_url']
        // $method_details['display_name'], $method_details['description'], $method_details['logo_url']
        $return_arr = [];
        $return_arr['countries'] = [];
        $return_arr['methods'] = [];

        foreach ($all_configured_methods as $method_id => $methods_per_country) {
            if (empty($methods_per_country) || !is_array($methods_per_country)) {
                continue;
            }

            foreach ($methods_per_country as $country_id => $country_settings) {
                $method_data = [];
                $method_data['surcharge'] = $country_settings['surcharge'];
                $method_data['fixed_amount'] = $country_settings['fixed_amount'];

                $method_details = [];
                $method_details['display_name'] = $country_settings['display_name'];
                $method_details['description'] = $country_settings['description'];
                $method_details['logo_url'] = $country_settings['logo_url'];

                $return_arr['methods'][$method_id] = $method_details;

                // for all countries for current method
                if (empty($country_id)) {
                    if (empty($country_methods['methods'][$method_id])
                        || !is_array($country_methods['methods'][$method_id])) {
                        continue;
                    }

                    foreach ($country_methods['methods'][$method_id] as $country_code => $country_name) {
                        $return_arr['countries'][$country_code][$method_id] = $method_data;
                    }
                } else { // for specific countries for current method (not implemented yet)
                    if (empty($country_methods['all'][$country_id])) {
                        continue;
                    }

                    $return_arr['countries'][$country_methods['all'][$country_id]['code']][$method_id] = $method_data;
                }
            }
        }

        return $return_arr;
    }

    /**
     * @param int $country_id
     * @param string $environment
     * @param bool|array $params
     *
     * @return array
     */
    public function getConfiguredMethodsForCountryID($country_id, $environment, $params = false)
    {
        $country_id = (int)$country_id;
        if (empty($country_id)) {
            return [];
        }

        if (empty($params) || !is_array($params)) {
            $params = [];
        }

        if (empty($params['id_in_index'])) {
            $params['id_in_index'] = false;
        }

        // 1. get a list of methods available for provided country
        // 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        // 3. overwrite default surcharges for particular cases (if available)
        // (s2p_gp_methods_configured.country_id = $country_id)

        //
        // START 1. get a list of methods available for provided country
        //

        $cm_collection = $this->_countryMethodFactory->create()->getCollection();
        $cm_collection->addFieldToSelect('*');
        $cm_collection->addFieldToFilter('main_table.country_id', $country_id);
        $cm_collection->addFieldToFilter('main_table.environment', $environment);

        $cm_collection->getSelect()->join(
            $cm_collection->getTable('s2p_gp_methods'),
            's2p_gp_methods.method_id = main_table.method_id'
        );

        $cm_collection->setOrder('priority', 'ASC');

        $methods_arr = [];
        $method_ids_arr = [];
        $enabled_method_ids_arr = [];

        while (($method_obj = $cm_collection->fetchItem())
               && ($method_arr = $method_obj->getData())) {
            if (empty($method_arr['method_id'])) {
                continue;
            }

            $method_ids_arr[] = $method_arr['method_id'];
            $methods_arr[$method_arr['method_id']] = $method_arr;
        }

        //
        // END 1. get a list of methods available for provided country
        //

        //
        // START 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        //
        $my_collection = $this->getCollection();
        $my_collection->addFieldToSelect('*');
        $my_collection->addFieldToFilter('country_id', 0);
        $my_collection->addFieldToFilter('method_id', [ 'in' => $method_ids_arr ]);

        while (($configured_method_obj = $my_collection->fetchItem())
               && ($configured_method_arr = $configured_method_obj->getData())) {
            if (empty($configured_method_arr['method_id'])) {
                continue;
            }

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];
            $methods_arr[$configured_method_arr['method_id']]['fixed_amount'] = $configured_method_arr['fixed_amount'];

            $enabled_method_ids_arr[$configured_method_arr['method_id']] = 1;
        }
        //
        // END 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        //

        //
        // START 3. overwrite default surcharges for particular cases (if available)
        // (s2p_gp_methods_configured.country_id = $country_id)
        //
        $my_collection = $this->getCollection();
        $my_collection->addFieldToSelect('*');
        $my_collection->addFieldToFilter('country_id', $country_id);
        $my_collection->addFieldToFilter('method_id', [ 'in' => $method_ids_arr ]);

        while (($configured_method_obj = $my_collection->fetchItem())
               && ($configured_method_arr = $configured_method_obj->getData())) {
            if (empty($configured_method_arr['method_id'])) {
                continue;
            }

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];
            $methods_arr[$configured_method_arr['method_id']]['fixed_amount'] = $configured_method_arr['fixed_amount'];

            $enabled_method_ids_arr[$configured_method_arr['method_id']] = 1;
        }
        //
        // END 3. overwrite default surcharges for particular cases (if available)
        // (s2p_gp_methods_configured.country_id = $country_id)
        //

        // clean methods array of methods that are not enabled
        $methods_result = [];
        foreach ($methods_arr as $method_id => $method_arr) {
            if (empty($enabled_method_ids_arr[$method_id])) {
                continue;
            }

            if (empty($params['id_in_index'])) {
                $methods_result[] = $method_arr;
            } else {
                $methods_result[$method_id] = $method_arr;
            }
        }

        return $methods_result;
    }

    /**
     * @param array $configured_methods_arr
     * @param string $environment
     *
     * @return array|bool
     */
    public function saveConfiguredMethods($configured_methods_arr, $environment)
    {
        if (!is_array($configured_methods_arr)) {
            return false;
        }

        // if( $this->_resource )
        //     $my_resource = $this->_resource;
        // else
        //     $my_resource = $this->getResource();

        $my_resource = $this->_configuredMethodsFactory->create();

        $saved_method_ids = [];
        $errors_arr = [];
        foreach ($configured_methods_arr as $method_id => $surcharge_per_countries) {
            $method_id = (int)$method_id;
            if (empty($method_id)
             || empty($surcharge_per_countries) || !is_array($surcharge_per_countries)
             || !($countries_ids = array_keys($surcharge_per_countries))) {
                continue;
            }

            $provided_countries = [];
            foreach ($surcharge_per_countries as $country_id => $country_surcharge) {
                $country_id = (int)$country_id;
                if (!is_array($country_surcharge)) {
                    continue;
                }

                if (empty($country_surcharge['surcharge'])) {
                    $country_surcharge['surcharge'] = 0;
                }
                if (empty($country_surcharge['fixed_amount'])) {
                    $country_surcharge['fixed_amount'] = 0;
                }

                if (!$my_resource->insertOrUpdate($method_id, $country_id, $environment, $country_surcharge)) {
                    $errors_arr[] = __('Error saving method ID %1, for country %2.', $method_id, $country_id);
                }

                $provided_countries[] = $country_id;
            }

            // Delete countries which are not provided for current method
            /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods\Collection $my_collection */
            $my_collection = $this->getCollection();
            $my_collection->addFieldToFilter('method_id', $method_id);
            $my_collection->addFieldToFilter('environment', $environment);
            if (!empty($provided_countries)) {
                $my_collection->addFieldToFilter('country_id', [ 'nin' => $provided_countries ]);
            }

            $my_resource->deleteFromCollection($my_collection);

            $saved_method_ids[] = $method_id;
        }

        // delete rest of methods not in $saved_method_ids array...
        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods\Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToFilter('environment', $environment);
        if (!empty($saved_method_ids)) {
            $my_collection->addFieldToFilter('method_id', [ 'nin' => $saved_method_ids ]);
        }

        $my_resource->deleteFromCollection($my_collection);

        if (!empty($errors_arr)) {
            return $errors_arr;
        }

        return true;
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getMethodID().'_'.$this->getCountryID()];
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment()
    {
        return $this->getData(self::ENVIRONMENT);
    }

    /**
     * @inheritDoc
     */
    public function getMethodID()
    {
        return $this->getData(self::METHOD_ID);
    }

    /**
     * @inheritDoc
     */
    public function getCountryID()
    {
        return $this->getData(self::COUNTRY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getSurcharge()
    {
        return $this->getData(self::SURCHARGE);
    }

    /**
     * @inheritDoc
     */
    public function getFixedAmount()
    {
        return $this->getData(self::FIXED_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setID($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function setEnvironment($environment)
    {
        return $this->setData(self::ENVIRONMENT, $environment);
    }

    /**
     * @inheritDoc
     */
    public function setMethodID($method_id)
    {
        return $this->setData(self::METHOD_ID, $method_id);
    }

    /**
     * @inheritDoc
     */
    public function setCountryID($country_id)
    {
        return $this->setData(self::COUNTRY_ID, $country_id);
    }

    /**
     * @inheritDoc
     */
    public function setSurcharge($surcharge)
    {
        return $this->setData(self::SURCHARGE, $surcharge);
    }

    /**
     * @inheritDoc
     */
    public function setFixedAmount($amount)
    {
        return $this->setData(self::FIXED_AMOUNT, $amount);
    }
}
