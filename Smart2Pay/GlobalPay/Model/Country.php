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
     * Check if country code exists in database and returns country details
     *
     * @param string $code
     * @return array
     */
    public function checkCode( $code )
    {
        return $this->_getResource()->checkCode( $code );
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
