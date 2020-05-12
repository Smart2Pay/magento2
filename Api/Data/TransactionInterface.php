<?php
namespace Smart2Pay\GlobalPay\Api\Data;

interface TransactionInterface
{
    const ID = 'id';
    const METHOD_ID = 'method_id';
    const PAYMENT_ID = 'payment_id';
    const MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';
    const SITE_ID = 'site_id';
    const ENVIRONMENT = 'environment';
    const EXTRA_DATA = 'extra_data';
    const TDSECURE = '3dsecure';
    const PAYMENT_STATUS = 'payment_status';
    const CREATED = 'created';
    const UPDATED = 'updated';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getID();

    /**
     * Get method ID
     *
     * @return int|null
     */
    public function getMethodId();

    /**
     * Get payment ID
     *
     * @return int|null
     */
    public function getPaymentId();

    /**
     * Get merchant transaction ID
     *
     * @return string|null
     */
    public function getMerchantTransactionId();

    /**
     * Get site ID
     *
     * @return int|null
     */
    public function getSiteId();

    /**
     * Get environment
     *
     * @return string|null
     */
    public function getEnvironment();

    /**
     * Get extra data
     *
     * @return string|null
     */
    public function getExtraData();

    /**
     * Get extra data as array
     *
     * @return array Returns an array with key-values of extra data for current transaction
     */
    public function getExtraDataArray();

    /**
     * Get 3D Secure
     *
     * @return int 3DSecure field
     */
    public function get3DSecure();

    /**
     * Get payment status
     *
     * @return int
     */
    public function getPaymentStatus();

    /**
     * Get creation timestamp
     *
     * @return int|null
     */
    public function getCreated();

    /**
     * Get update timestamp
     *
     * @return int|null
     */
    public function getUpdated();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setID($id);

    /**
     * Set method ID
     *
     * @param int $method_id
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setMethodID($method_id);

    /**
     * Set payment ID
     *
     * @param int $payment_id
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setPaymentID($payment_id);

    /**
     * Set merchant transaction ID
     *
     * @param string $mt_id
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setMerchantTransactionID($mt_id);

    /**
     * Set site ID
     *
     * @param int $site_id
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setSiteID($site_id);

    /**
     * Set environment
     *
     * @param string $environment
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setEnvironment($environment);

    /**
     * Set extra data
     *
     * @param string|array $data
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setExtraData($data);

    /**
     * Set extra data
     *
     * @param array $data_arr
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setExtraDataArray($data_arr);

    /**
     * Set 3D Secure
     *
     * @param int $tdsecure
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function set3DSecure($tdsecure);

    /**
     * Set payment status
     *
     * @param int $status
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setPaymentStatus($status);

    /**
     * Set creation time
     *
     * @param int $time
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setCreated($time);

    /**
     * Set update time
     *
     * @param int $time
     * @return \Smart2Pay\GlobalPay\Api\Data\TransactionInterface
     */
    public function setUpdated($time);
}
