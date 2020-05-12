<?php
namespace Smart2Pay\GlobalPay\Api\Data;

interface CountryMethodInterface
{
    const ID = 'id';
    const ENVIRONMENT = 'environment';
    const METHOD_ID = 'method_id';
    const COUNTRY_ID = 'country_id';
    const PRIORITY = 'priority';

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
     * Get Priority
     *
     * @return int|null
     */
    public function getPriority();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setID($id);

    /**
     * Set Method ID
     *
     * @param string $environment
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setEnvironment($environment);

    /**
     * Set Method ID
     *
     * @param int $method_id
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setMethodID($method_id);

    /**
     * Set Country ID
     *
     * @param int $country_id
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setCountryID($country_id);

    /**
     * Set Priority
     *
     * @param int $priority
     * @return \Smart2Pay\GlobalPay\Api\Data\ConfiguredMethodsInterface
     */
    public function setPriority($priority);
}
