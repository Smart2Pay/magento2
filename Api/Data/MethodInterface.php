<?php
namespace Smart2Pay\GlobalPay\Api\Data;

interface MethodInterface
{
    const ID = 'id';
    const METHOD_ID = 'method_id';
    const ENVIRONMENT = 'environment';
    const DISPLAY_NAME = 'display_name';
    const PROVIDER_VALUE = 'provider_value';
    const DESCRIPTION = 'description';
    const LOGO_URL = 'logo_url';
    const GUARANTEED = 'guaranteed';
    const ACTIVE = 'active';

    /**
     * Get ID
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
     * Get Display Name
     *
     * @return string|null
     */
    public function getDisplayName();

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
     * Set ID
     *
     * @param int $id
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setID($id);

    /**
     * Set Method ID
     *
     * @param int $method_id
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setMethodID($method_id);

    /**
     * Set Provider Value
     *
     * @param string $environment
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setEnvironment($environment);

    /**
     * Set Display Name
     *
     * @param string $display_name
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setDisplayName($display_name);

    /**
     * Set Description
     *
     * @param string $description
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setDescription($description);

    /**
     * Set Logo URL
     *
     * @param string $logo_url
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setLogoURL($logo_url);

    /**
     * Set is guaranteed
     *
     * @param int|bool $is_guaranteed
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setIsGuaranteed($is_guaranteed);

    /**
     * Set is active
     *
     * @param int|bool $is_active
     * @return \Smart2Pay\GlobalPay\Api\Data\MethodInterface
     */
    public function setIsActive($is_active);
}
