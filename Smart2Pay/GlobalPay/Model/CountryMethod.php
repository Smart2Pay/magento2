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
