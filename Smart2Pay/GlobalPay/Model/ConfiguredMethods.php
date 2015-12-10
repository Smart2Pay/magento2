<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class ConfiguredMethods
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods _getResource()
 * @package Smart2Pay\GlobalPay\Model
 */
class ConfiguredMethods extends \Magento\Framework\Model\AbstractModel implements ConfiguredMethodsInterface, IdentityInterface
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

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Smart2Pay\GlobalPay\Model\CountryMethodFactory $countryMethodFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_countryMethodFactory = $countryMethodFactory;

        parent::__construct( $context, $registry, $resource, $resourceCollection, $data );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods' );
    }

    /**
     * Check if a method is configured for $method_id, country_id pair
     * return details if method is configured
     *
     * @param int $method_id
     * @return int
     */
    public function checkMethodCountryID( $method_id, $country_id )
    {
        return $this->_getResource()->checkMethodCountryID( $method_id, $country_id );
    }

    /**
     * Check if method_id key exists
     * return method id if method exists
     *
     * @param int $method_id
     * @return array
     */
    public function getMethodsForCountry( $country_id )
    {
        return $this->_getResource()->getMethodsForCountry( $country_id );
    }

    /**
     * Check if method_id key exists
     * return method id if method exists
     *
     * @param int $method_id
     * @return int
     */
    public function getCountriesForMethod( $method_id )
    {
        return $this->_getResource()->getCountriesForMethod( $method_id );
    }

    /**
     * @param bool|array $params
     *
     * @return array
     */
    public function getAllConfiguredMethods( $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        // $return_arr[{method_ids}][{country_ids}]['surcharge'], $return_arr[{method_ids}][{country_ids}]['base_amount'], ...
        $return_arr = array();

        $collection = $this->getCollection();

        $collection->addFieldToSelect( '*' );

        while( ($configured_method_obj = $collection->fetchItem())
               and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $return_arr[$configured_method_arr['method_id']][$configured_method_arr['country_id']] = $configured_method_arr;
        }

        return $return_arr;
    }

    public function getConfiguredMethodsForCountryID( $country_id, $params = false )
    {
        $country_id = intval( $country_id );
        if( empty( $country_id ) )
            return array();

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['id_in_index'] ) )
            $params['id_in_index'] = false;

        // 1. get a list of methods available for provided country
        // 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        // 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)

        //
        // START 1. get a list of methods available for provided country
        //

        $cm_collection = $this->_countryMethodFactory->create()->getCollection();
        $cm_collection->addFieldToSelect( '*' );
        $cm_collection->addFieldToFilter( 'country_id', $country_id );

        $cm_collection->getSelect()->join(
            $cm_collection->getTable( 's2p_gp_methods' ),
            's2p_gp_methods.method_id = main_table.method_id'
        );

        $cm_collection->setOrder( 'priority', 'ASC' );

        $methods_arr = array();
        $method_ids_arr = array();
        $enabled_method_ids_arr = array();


        while( ($method_obj = $cm_collection->fetchItem())
               and ($method_arr = $method_obj->getData()) )
        {
            if( empty( $method_arr['method_id'] ) )
                continue;

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
        $my_collection->addFieldToSelect( '*' );
        $my_collection->addFieldToFilter( 'country_id', 0 );
        $my_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        while( ($configured_method_obj = $my_collection->fetchItem())
               and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];
            $methods_arr[$configured_method_arr['method_id']]['fixed_amount'] = $configured_method_arr['fixed_amount'];

            $enabled_method_ids_arr[$configured_method_arr['method_id']] = 1;
        }
        //
        // END 2. get default surcharge (s2p_gp_methods_configured.country_id = 0)
        //

        //
        // START 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)
        //
        $my_collection = $this->getCollection();
        $my_collection->addFieldToSelect( '*' );
        $my_collection->addFieldToFilter( 'country_id', $country_id );
        $my_collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        while( ($configured_method_obj = $my_collection->fetchItem())
               and ($configured_method_arr = $configured_method_obj->getData()) )
        {
            if( empty( $configured_method_arr['method_id'] ) )
                continue;

            $methods_arr[$configured_method_arr['method_id']]['surcharge'] = $configured_method_arr['surcharge'];
            $methods_arr[$configured_method_arr['method_id']]['fixed_amount'] = $configured_method_arr['fixed_amount'];

            $enabled_method_ids_arr[$configured_method_arr['method_id']] = 1;
        }
        //
        // END 3. overwrite default surcharges for particular cases (if available) (s2p_gp_methods_configured.country_id = $country_id)
        //

        // clean methods array of methods that are not enabled
        $methods_result = array();
        foreach( $methods_arr as $method_id => $method_arr )
        {
            if( empty( $enabled_method_ids_arr[$method_id] ) )
                continue;

            if( empty( $params['id_in_index'] ) )
                $methods_result[] = $method_arr;
            else
                $methods_result[$method_id] = $method_arr;
        }

        return $methods_result;
    }

    /**
     * @param array $configured_methods_arr
     *
     * @return array|bool
     */
    public function saveConfiguredMethods( $configured_methods_arr )
    {
        if( !is_array( $configured_methods_arr ) )
            return false;

        $my_resource = $this->_getResource();

        $saved_method_ids = array();
        $errors_arr = array();
        foreach( $configured_methods_arr as $method_id => $surcharge_per_countries )
        {
            $method_id = intval( $method_id );
            if( empty( $method_id )
             or empty( $surcharge_per_countries ) or !is_array( $surcharge_per_countries )
             or !($countries_ids = array_keys( $surcharge_per_countries )) )
                continue;

            $provided_countries = array();
            foreach( $surcharge_per_countries as $country_id => $country_surcharge )
            {
                $country_id = intval( $country_id );
                if( !is_array( $country_surcharge ) )
                    continue;

                if( empty( $country_surcharge['surcharge'] ) )
                    $country_surcharge['surcharge'] = 0;
                if( empty( $country_surcharge['fixed_amount'] ) )
                    $country_surcharge['fixed_amount'] = 0;

                if( !$my_resource->insert_or_update( $method_id, $country_id, $country_surcharge ) )
                    $errors_arr[] = __( 'Error saving method ID %1, for country %2.', $method_id, $country_id );

                $provided_countries[] = $country_id;
            }

            // Delete countries which are not provided for current method
            /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods\Collection $my_collection */
            $my_collection = $this->getCollection();
            $my_collection->addFieldToFilter( 'method_id', $method_id );
            if( !empty( $provided_countries ) )
                $my_collection->addFieldToFilter( 'country_id', array( 'nin' => $provided_countries ) );

            $my_resource->deleteFromCollection( $my_collection );

            $saved_method_ids[] = $method_id;
        }

        // delete rest of methods not in $saved_method_ids array...
        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods\Collection $my_collection */
        $my_collection = $this->getCollection();
        if( !empty( $saved_method_ids ) )
            $my_collection->addFieldToFilter( 'method_id', array( 'nin' => $saved_method_ids ) );

        $my_resource->deleteFromCollection( $my_collection );

        if( !empty( $errors_arr ) )
            return $errors_arr;

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
        return $this->getData( self::ID );
    }

    /**
     * @inheritDoc
     */
    public function getMethodID()
    {
        return $this->getData( self::METHOD_ID );
    }

    /**
     * @inheritDoc
     */
    public function getCountryID()
    {
        return $this->getData( self::COUNTRY_ID );
    }

    /**
     * @inheritDoc
     */
    public function getSurcharge()
    {
        return $this->getData( self::SURCHARGE );
    }

    /**
     * @inheritDoc
     */
    public function getFixedAmount()
    {
        return $this->getData( self::FIXED_AMOUNT );
    }

    /**
     * @inheritDoc
     */
    public function setID( $id )
    {
        return $this->setData( self::ID, $id );
    }

    /**
     * @inheritDoc
     */
    public function setMethodID( $method_id )
    {
        return $this->setData( self::METHOD_ID, $method_id );
    }

    /**
     * @inheritDoc
     */
    public function setCountryID( $country_id )
    {
        return $this->setData( self::COUNTRY_ID, $country_id );
    }

    /**
     * @inheritDoc
     */
    public function setSurcharge( $surcharge )
    {
        return $this->setData( self::SURCHARGE, $surcharge );
    }

    /**
     * @inheritDoc
     */
    public function setFixedAmount( $amount )
    {
        return $this->setData( self::FIXED_AMOUNT, $amount );
    }
}
