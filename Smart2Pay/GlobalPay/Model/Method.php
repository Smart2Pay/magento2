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
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\Method' );
    }

    /**
     * Check if method id key exists
     * return post id if post exists
     *
     * @param int $method_id
     * @return int
     */
    public function checkMethodID( $method_id )
    {
        return $this->_getResource()->checkMethodID( $method_id );
    }

    public function getAllActiveMethods( $params = false )
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

        $collection = $this->getCollection();

        $collection->addFieldToFilter( 'active', 1 );

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
                $return_arr[$method_arr['method_id']]['countries_list'] = $this->getCountriesForMethod( $method_arr['method_id'] );
            else
                $return_arr[$method_arr['method_id']]['countries_list'] = array();
        }

        return $return_arr;
    }

    public function getCountriesForMethod( $method_id )
    {
        $method_id = intval( $method_id );
        if( empty( $method_id ) )
            return array();

        $collection = $this->_countryMethodFactory->create()->getCollection();

        $collection->addFieldToSelect( '*' );

        $collection->addFieldToFilter( 'main_table.method_id', $method_id );

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
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getMethodID()];
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
    public function getProviderValue()
    {
        return $this->getData( self::PROVIDER_VALUE );
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
    public function setMethodID( $method_id )
    {
        return $this->setData( self::METHOD_ID, $method_id );
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
    public function setProviderValue( $provider_value )
    {
        return $this->setData( self::PROVIDER_VALUE, $provider_value );
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
