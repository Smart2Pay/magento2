<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\CountryInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class Country
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\Country _getResource()
 * @package Smart2Pay\GlobalPay\Model
 */
class Country extends \Magento\Framework\Model\AbstractModel implements CountryInterface, IdentityInterface
{
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'smart2pay_globalpay_country';

    const INTERNATIONAL_CODE = 'AA';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smart2pay_globalpay_country';

    private static $db_countries = false;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\Country' );
    }

    /**
     * Check if country code exists in database and returns country id
     *
     * @param string $code
     * @return int
     */
    public function checkCode( $code )
    {
        return $this->_getResource()->checkCode( $code );
    }

    public function getCountriesArray()
    {
        if( !empty( self::$db_countries ) )
            return self::$db_countries;

        $collection = $this->getCollection();

        $collection->addFieldToSelect( '*' );

        self::$db_countries = array();

        while( ($country_obj = $collection->fetchItem())
           and ($country_arr = $country_obj->getData()) )
        {
            if( empty( $country_arr['country_id'] ) )
                continue;

            self::$db_countries['items'][$country_arr['country_id']] = $country_arr;
            self::$db_countries['ids'][$country_arr['country_id']] = $country_arr['code'];
            self::$db_countries['codes'][$country_arr['code']] = $country_arr['country_id'];
        }

        return self::$db_countries;
    }

    /**
     * Get countries as country codes for key and country id as value
     * @return array
     */
    public function getCountriesCodeAsKey()
    {
        if( !($countries_arr = $this->getCountriesArray())
         or empty( $countries_arr['codes'] )
         or !is_array( $countries_arr['codes'] ) )
            return array();

        return $countries_arr['codes'];
    }

    public function getCountryIDToCountryCodeArray()
    {
        if( !$this->getCountriesArray()
         or empty( self::$db_countries['ids'] ) )
            return array();

        return self::$db_countries['ids'];
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getCountryId()];
    }

    /**
     * @inheritDoc
     */
    public function getCountryId()
    {
        return $this->getData( self::COUNTRY_ID );
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return $this->getData( self::CODE );
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData( self::NAME );
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
    public function setCode( $code )
    {
        return $this->setData( self::CODE, $code );
    }

    /**
     * @inheritDoc
     */
    public function setName( $name )
    {
        return $this->setData( self::NAME, $name );
    }
}
