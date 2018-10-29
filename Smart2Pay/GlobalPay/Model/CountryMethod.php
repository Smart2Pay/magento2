<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\CountryMethodInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class Country
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod _getResource()
 * @package Smart2Pay\GlobalPay\Model
 */
class CountryMethod extends \Magento\Framework\Model\AbstractModel implements CountryMethodInterface, IdentityInterface
{
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'smart2pay_globalpay_countrymethod';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smart2pay_globalpay_countrymethod';

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\CountryFactory
     */
    private $_countryFactory;

    /**
     * Logger Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\LoggerFactory
     */
    private $_loggerFactory;

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Smart2Pay\GlobalPay\Model\CountryFactory $countryFactory,
        \Smart2Pay\GlobalPay\Model\LoggerFactory $loggerFactory,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_s2pHelper = $s2pHelper;
        $this->_countryFactory = $countryFactory;
        $this->_loggerFactory = $loggerFactory;

        parent::__construct( $context, $registry, $resource, $resourceCollection, $data );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod' );
    }

    /**
     * Check if a method is configured for $method_id, country_id pair
     * return details if method is configured
     *
     * @param int $method_id
     * @param int $country_id
     * @param bool|string $environment
     * @return array
     */
    public function checkMethodCountryID( $method_id, $country_id, $environment = false )
    {
        return $this->_getResource()->checkMethodCountryID( $method_id, $country_id, $environment );
    }

    /**
     * Check if method_id key exists
     * return method id if method exists
     *
     * @param int $country_id
     * @param bool|string $environment
     * @return array
     */
    public function getMethodsForCountry( $country_id, $environment = false )
    {
        return $this->_getResource()->getMethodsForCountry( $country_id, $environment );
    }

    /**
     * Get all countries for specified method and environment
     *
     * @param int $method_id
     * @param bool|string $environment
     * @return array
     */
    public function getCountriesForMethod( $method_id, $environment = false )
    {
        return $this->_getResource()->getCountriesForMethod( $method_id, $environment );
    }

    public function getCountriesForMethodsList( $methods_arr = false, $environment = false )
    {
        if( empty( $environment ) )
            $environment = $this->_s2pHelper->getEnvironment();

        $method_ids_arr = false;
        if( !empty( $methods_arr ) and is_array( $methods_arr ) )
        {
            $method_ids_arr = array();
            foreach( $methods_arr as $method_id )
            {
                $method_id = intval( $method_id );
                if( empty( $method_id ) )
                    continue;

                $method_ids_arr[] = $method_id;
            }
        }

        $collection = $this->getCollection();
        $collection->addFieldToFilter( 'main_table.environment', $environment );

        if( !empty( $method_ids_arr ) )
        {
            if( count( $method_ids_arr ) == 1 )
                $collection->addFieldToFilter( 'main_table.method_id', $method_ids_arr[0] );
            else
                $collection->addFieldToFilter( 'main_table.method_id', array( 'in' => $method_ids_arr ) );
        }

        $country_collection = $this->_countryFactory->create()->getCollection();

        $collection->getSelect()->join(
            $country_collection->getMainTable(),
            'main_table.country_id = '.$country_collection->getMainTable().'.country_id'
        );

        $return_arr = array();
        $return_arr['all'] = array();
        $return_arr['methods'] = array();

        while( ($country_method_obj = $collection->fetchItem())
           and ($country_method_arr = $country_method_obj->getData()) )
        {
            if( empty( $country_method_arr['country_id'] ) )
                continue;

            if( !isset( $return_arr['all'][$country_method_arr['country_id']] ) )
            {
                $return_arr['all'][$country_method_arr['country_id']] = array(
                    'code' => $country_method_arr['code'],
                    'name' => $country_method_arr['name'],
                );
            }

            $return_arr['methods'][$country_method_arr['method_id']][$country_method_arr['code']] = $country_method_arr['name'];
        }

        return $return_arr;
    }

    /**
     * @param string $environment
     * @param int $method_id
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function deleteCountryMethodsForEnvironment( $environment, $method_id = 0 )
    {
        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod $my_resource */
        $my_resource = $this->getResource();

        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod\Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToFilter( 'environment', $environment );
        if( !empty( $method_id ) )
            $my_collection->addFieldToFilter( 'method_id', $method_id );

        return $my_resource->deleteFromCollection( $my_collection );
    }

    /**
     * @param int $method_id
     * @param array $countries_arr
     * @param string $environment
     * @param bool|array $params
     *
     * @throws \Exception
     *
     * @return bool|string
     */
    public function updateMethodCountries( $method_id, $countries_arr, $environment, $params = false )
    {
        $country_obj = $this->_countryFactory->create();
        $s2pLogger = $this->_loggerFactory->create();

        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod $my_resource */
        $my_resource = $this->getResource();

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['delete_before_update'] ) )
            $params['delete_before_update'] = true;

        $method_id = intval( $method_id );
        $environment = strtolower( trim( $environment ) );
        if( empty( $method_id )
         or empty( $environment ) or !in_array( $environment, array( 'demo', 'test', 'live' ) ) )
            return 'Bad parameters when updating method countries.';

        if( !($db_countries_arr = $country_obj->getCountriesCodeAsKey()) )
        {
            $s2pLogger->write( 'Couldn\'t retrieve countries from database.', 'update_method_countries' );

            return 'Couldn\'t retrieve countries from database.';
        }

        if( !empty( $params['delete_before_update'] )
        and !$this->deleteCountryMethodsForEnvironment( $environment, $method_id ) )
        {
            $s2pLogger->write( 'Couldn\'t delete existing method countries.', 'update_method_countries' );

            return 'Couldn\'t delete existing method countries.';
        }

        foreach( $countries_arr as $country )
        {
            $country = strtoupper( trim( $country ) );
            if( empty( $db_countries_arr[$country] ) )
                continue;

            if( !$my_resource->insertOrUpdate( $method_id, $db_countries_arr[$country], $environment ) )
            {
                $s2pLogger->write( 'Couldn\'t update method countries for method #'.$method_id.'.', 'update_method_countries' );

                $this->deleteCountryMethodsForEnvironment( $environment, $method_id );

                return 'Couldn\'t update method countries for method #'.$method_id.'.';
            }
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
        return $this->getData( self::ID );
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment()
    {
        return $this->getData( self::ENVIRONMENT );
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
    public function getPriority()
    {
        return $this->getData( self::PRIORITY );
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
    public function setEnvironment( $environment )
    {
        return $this->setData( self::ENVIRONMENT, $environment );
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
    public function setPriority( $priority )
    {
        return $this->setData( self::PRIORITY, $priority );
    }
}
