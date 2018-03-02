<?php
namespace Smart2Pay\GlobalPay\Api\Data;


interface ConfiguredMethodsInterface
{
    const ID = 'id';
    const ENVIRONMENT = 'environment';
    const METHOD_ID = 'method_id';
    const COUNTRY_ID = 'country_id';
    const SURCHARGE = 'surcharge';
    const FIXED_AMOUNT = 'fixed_amount';

    /**
     * Get  ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get Environment
     *
     * @return string|null
     */
    public function getEnvironment();

    /**
     * Get Method ID
     *
     * @return int|null
     */
    public function getMethodID();

    /**
     * Get Country ID
     *
     * @return int|null
     */
    public function getCountryID();

    /**
     * Get Surcharge
     *
     * @return float|null
     */
    public function getSurcharge();

    /**
     * Get Fixed surcharge amount
     *
     * @return float|null
     */
    public function getFixedAmount();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setID( $id );

    /**
     * Set Environment
     *
     * @param string $environment
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setEnvironment( $environment );

    /**
     * Set Method ID
     *
     * @param int $method_id
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setMethodID( $method_id );

    /**
     * Set Country ID
     *
     * @param int $country_id
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setCountryID( $country_id );

    /**
     * Set Surcharge amount
     *
     * @param float $surcharge_amount
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setSurcharge( $surcharge_amount );

    /**
     * Set Surcharge fixed amount
     *
     * @param float $fixed_amount
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setFixedAmount( $fixed_amount );

}
