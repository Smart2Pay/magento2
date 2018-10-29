<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\MethodInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class Method
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\Method _getResource()
 * @package Smart2Pay\GlobalPay\Model
 */
class Method extends \Magento\Framework\Model\AbstractModel implements MethodInterface, IdentityInterface
{
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'smart2pay_globalpay_method';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smart2pay_globalpay_method';

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\CountryMethodFactory
     */
    private $_countryMethodFactory;

    /**
     * Logger Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\LoggerFactory
     */
    private $_loggerFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Smart2Pay\GlobalPay\Model\CountryMethodFactory $countryMethodFactory,
        \Smart2Pay\GlobalPay\Model\LoggerFactory $loggerFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_loggerFactory = $loggerFactory;
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
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\Method' );
    }

    /**
     * Check if method id key exists
     * return post id if post exists
     *
     * @param int $method_id
     * @param bool|string $environment
     * @return int
     */
    public function checkMethodID( $method_id, $environment = false )
    {
        return $this->_getResource()->checkMethodID( $method_id, $environment );
    }

    public function getByMethodID( $method_id, $environment = false )
    {
        return $this->_getResource()->getByMethodID( $method_id, $environment );
    }

    public function getAllActiveMethods( $environment, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['include_countries'] ) )
            $params['include_countries'] = false;
        if( !isset( $params['method_ids'] ) or !is_array( $params['method_ids'] ) )
            $params['method_ids'] = false;
        if( empty( $params['order_by'] ) or !in_array( $params['order_by'], array( 'display_name', 'method_id' ) ) )
            $params['order_by'] = 'display_name';

        // we received an empty array of ids, so we should return empty result...
        if( is_array( $params['method_ids'] ) and empty( $params['method_ids'] ) )
            return array();

        $method_ids_arr = false;
        if( !empty( $params['method_ids'] ) )
        {
            $method_ids_arr = array();
            foreach( $params['method_ids'] as $method_id )
            {
                $method_id = intval( $method_id );
                if( empty( $method_id ) )
                    continue;

                $method_ids_arr[] = $method_id;
            }
        }

        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\Method\Collection $collection */
        $collection = $this->getCollection();

        $collection->addFieldToFilter( 'active', 1 );
        $collection->addFieldToFilter( 'environment', $environment );

        if( !empty( $method_ids_arr ) )
            $collection->addFieldToFilter( 'method_id', array( 'in' => $method_ids_arr ) );

        $collection->setOrder( $params['order_by'], $collection::SORT_ORDER_ASC );

        $return_arr = array();

        while( ($method_obj = $collection->fetchItem())
               and ($method_arr = $method_obj->getData()) )
        {
            if( empty( $method_arr['method_id'] ) )
                continue;

            $return_arr[$method_arr['method_id']] = $method_arr;

            if( !empty( $params['include_countries'] ) )
                $return_arr[$method_arr['method_id']]['countries_list'] = $this->getCountriesForMethod( $method_arr['method_id'], $environment );
            else
                $return_arr[$method_arr['method_id']]['countries_list'] = array();
        }

        return $return_arr;
    }

    public function getCountriesForMethod( $method_id, $environment )
    {
        $method_id = intval( $method_id );
        if( empty( $method_id ) )
            return array();

        $collection = $this->_countryMethodFactory->create()->getCollection();

        $collection->addFieldToSelect( '*' );

        $collection->addFieldToFilter( 'main_table.method_id', $method_id );
        $collection->addFieldToFilter( 'main_table.environment', $environment );

        $collection->getSelect()->join(
            $collection->getTable( 's2p_gp_countries' ),
            's2p_gp_countries.country_id = main_table.country_id' );

        $collection->setOrder( 's2p_gp_countries.name', $collection::SORT_ORDER_ASC );

        $return_arr = array();

        while( ($country_obj = $collection->fetchItem())
               and ($country_arr = $country_obj->getData()) )
        {
            if( empty( $country_arr['country_id'] ) )
                continue;

            $return_arr[$country_arr['country_id']] = $country_arr;
        }

        return $return_arr;

    }

    /**
     * @param bool|string $environment
     * @throws \Exception
     * @return bool
     */
    public function deleteMethodsForEnvironment( $environment )
    {
        $country_method_obj = $this->_countryMethodFactory->create();

        if( !$country_method_obj->deleteCountryMethodsForEnvironment( $environment ) )
            return false;

        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\Method $my_resource */
        $my_resource = $this->getResource();

        /** @var \Smart2Pay\GlobalPay\Model\ResourceModel\Method\Collection $my_collection */
        $my_collection = $this->getCollection();
        $my_collection->addFieldToFilter( 'environment', $environment );

        return $my_resource->deleteFromCollection( $my_collection );
    }

    /**
     * @param array $methods_arr
     * @param string $environment
     * @return string|bool
     */
    public function saveMethodsFromSDKResponse( $methods_arr, $environment )
    {
        try
        {
            $s2pLogger = $this->_loggerFactory->create();
            $country_method_obj = $this->_countryMethodFactory->create();

            $my_resource = $this->_getResource();
        } catch( \Exception $e )
        {
            if( !empty( $s2pLogger ) )
                $s2pLogger->write( 'Error initializing resources.', 'SDK_methods_update' );

            return 'Please provide a valid environment.';
        }

        if( empty( $environment ) or !is_string( $environment ) )
        {
            $s2pLogger->write( 'Environment is not a string.', 'SDK_methods_update' );

            return 'Please provide a valid environment.';
        }

        if( !is_array( $methods_arr ) )
        {
            $s2pLogger->write( 'SDK methods response is not an array.', 'SDK_methods_update' );

            return 'You should provide an array of payment methods to be saved.';
        }

        $s2pLogger->write( 'Updating '.count( $methods_arr ).' methods for environment '.$environment.' from SDK response.', 'SDK_methods_update' );

        try
        {
            if( !$this->deleteMethodsForEnvironment( $environment ) )
            {
                $s2pLogger->write( 'Couldn\'t delete existing methods from database.', 'SDK_methods_update' );

                return 'Couldn\'t delete existing methods from database.';
            }
        } catch( \Exception $e )
        {
            $s2pLogger->write( 'Couldn\'t delete existing methods from database: '.$e->getMessage(), 'SDK_methods_update' );

            return 'Couldn\'t delete existing methods from database.';
        }

        foreach( $methods_arr as $method_arr )
        {
            if( empty( $method_arr ) or !is_array( $method_arr )
             or empty( $method_arr['id'] ) )
                continue;

            $row_method_arr = array();
            $row_method_arr['display_name'] = $method_arr['displayname'];
            $row_method_arr['description'] = $method_arr['description'];
            $row_method_arr['logo_url'] = $method_arr['logourl'];
            $row_method_arr['guaranteed'] = (!empty( $method_arr['guaranteed'] )?1:0);
            $row_method_arr['active'] = (!empty( $method_arr['active'] )?1:0);

            if( !($db_method = $my_resource->insertOrUpdate( $method_arr['id'], $environment, $row_method_arr )) )
            {
                $s2pLogger->write( 'Error saving method details in database (#'.$method_arr['id'].').', 'SDK_methods_update' );

                $this->deleteMethodsForEnvironment( $environment );

                return 'Error saving method details in database (#'.$method_arr['id'].').';
            }

            if( !empty( $method_arr['countries'] ) and is_array( $method_arr['countries'] ) )
            {
                if( true !== ($error_msg = $country_method_obj->updateMethodCountries( $method_arr['id'], $method_arr['countries'],
                                                                                       $environment, array( 'delete_before_update' => false ) )) )
                {
                    $s2pLogger->write( 'Error saving method countries in database (#'.$method_arr['id'].').', 'SDK_methods_update' );

                    $this->deleteMethodsForEnvironment( $environment );

                    return $error_msg;
                }
            }

            $saved_method_ids[] = $db_method['id'];
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
        return [self::CACHE_TAG . '_' . $this->getID()];
    }

    /**
     * @inheritDoc
     */
    public function getId()
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
    public function getDisplayName()
    {
        return $this->getData( self::DISPLAY_NAME );
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->getData( self::DESCRIPTION );
    }

    /**
     * @inheritDoc
     */
    public function getLogoURL()
    {
        return $this->getData( self::LOGO_URL );
    }

    /**
     * @inheritDoc
     */
    public function isGuaranteed()
    {
        return $this->getData( self::GUARANTEED );
    }

    /**
     * @inheritDoc
     */
    public function isActive()
    {
        return $this->getData( self::ACTIVE );
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
    public function setEnvironment( $environment )
    {
        return $this->setData( self::ENVIRONMENT, $environment );
    }

    /**
     * @inheritDoc
     */
    public function setDisplayName( $display_name )
    {
        return $this->setData( self::DISPLAY_NAME, $display_name );
    }

    /**
     * @inheritDoc
     */
    public function setDescription( $description )
    {
        return $this->setData( self::DESCRIPTION, $description );
    }

    /**
     * @inheritDoc
     */
    public function setLogoURL( $logo_url )
    {
        return $this->setData( self::LOGO_URL, $logo_url );
    }

    /**
     * @inheritDoc
     */
    public function setIsGuaranteed( $is_guaranteed )
    {
        return $this->setData( self::GUARANTEED, $is_guaranteed );
    }

    /**
     * @inheritDoc
     */
    public function setIsActive( $is_active )
    {
        return $this->setData( self::ACTIVE, $is_active );
    }
}
