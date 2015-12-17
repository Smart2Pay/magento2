<?php
namespace Smart2Pay\GlobalPay\Api\Data;


interface CountryInterface
{
    const COUNTRY_ID = 'country_id';
    const CODE = 'code';
    const NAME = 'name';

    /**
     * Get country ID
     *
     * @return int|null
     */
    public function getCountryId();

    /**
     * Get country code
     *
     * @return string|null
     */
    public function getCode();

    /**
     * Get country Name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Smart2Pay\GlobalPay\Api\Data\CountryInterface
     */
    public function setCountryID( $country_id );

    /**
     * Set country code
     *
     * @param string $code
     * @return \Smart2Pay\GlobalPay\Api\Data\CountryInterface
     */
    public function setCode( $code );

    /**
     * Set country name
     *
     * @param string $name
     * @return \Smart2Pay\GlobalPay\Api\Data\CountryInterface
     */
    public function setName( $name );

}
