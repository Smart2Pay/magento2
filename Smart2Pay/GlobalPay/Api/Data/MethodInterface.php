<?php
namespace Smart2Pay\GlobalPay\Api\Data;


interface MethodInterface
{
    const METHOD_ID = 'method_id';
    const DISPLAY_NAME = 'display_name';
    const PROVIDER_VALUE = 'provider_value';
    const DESCRIPTION = 'description';
    const LOGO_URL = 'logo_url';
    const GUARANTEED = 'guaranteed';
    const ACTIVE = 'active';

    /**
     * Get Method ID
     *
     * @return int|null
     */
    public function getMethodID();

    /**
     * Get Display Name
     *
     * @return string|null
     */
    public function getDisplayName();

    /**
     * Get Provider Value
     *
     * @return string|null
     */
    public function getProviderValue();

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Get Logo URL
     *
     * @return string|null
     */
    public function getLogoURL();

    /**
     * Get is guaranteed
     *
     * @return bool|null
     */
    public function isGuaranteed();

    /**
     * Get is active
     *
     * @return bool|null
     */
    public function isActive();

    /**
     * Set Method ID
     *
     * @param int $method_id
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setMethodID( $method_id );

    /**
     * Set Display Name
     *
     * @param string $display_name
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setDisplayName( $display_name );

    /**
     * Set Provider Value
     *
     * @param string $display_name
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setProviderValue( $provider_value );

    /**
     * Set Description
     *
     * @param string $description
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setDescription( $description );

    /**
     * Set Logo URL
     *
     * @param string $logo_url
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setLogoURL( $logo_url );

    /**
     * Set is guaranteed
     *
     * @param int|bool $is_guaranteed
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setIsGuaranteed( $is_guaranteed );

    /**
     * Set is active
     *
     * @param int|bool $is_active
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setIsActive( $is_active );


}
