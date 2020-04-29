<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\TransactionInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class Transaction
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\Transaction _getResource()
 * @package Smart2Pay\GlobalPay\Model
 */
class Transaction extends \Magento\Framework\Model\AbstractModel implements TransactionInterface, IdentityInterface
{
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'smart2pay_globalpay_transaction';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smart2pay_globalpay_transaction';

    /**
     * Helper
     *
     * @var \Smart2Pay\GlobalPay\Helper\S2pHelper
     */
    protected $_helper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct( $context, $registry, $resource, $resourceCollection, $data );

        $this->_helper = $helperSmart2Pay;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\Transaction' );
    }

    /**
     * Check if country code exists in database and returns country details
     *
     * @param string $mt_id
     * @return array
     */
    public function checkMerchantTransactionId( $mt_id )
    {
        return $this->_getResource()->checkMerchantTransactionId( $mt_id );
    }

    public function loadByMerchantTransactionId( $mt_id )
    {
        return parent::load( $mt_id, self::MERCHANT_TRANSACTION_ID );
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getMerchantTransactionId()];
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
    public function getMethodId()
    {
        return $this->getData( self::METHOD_ID );
    }

    /**
     * @inheritDoc
     */
    public function getPaymentId()
    {
        return $this->getData( self::PAYMENT_ID );
    }

    /**
     * @inheritDoc
     */
    public function getMerchantTransactionId()
    {
        return $this->getData( self::MERCHANT_TRANSACTION_ID );
    }

    /**
     * @inheritDoc
     */
    public function getSiteId()
    {
        return $this->getData( self::SITE_ID );
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment()
    {
        return $this->getData( self::ENVIRONMENT );
    }

    /**
     * @inheritDoc
     */
    public function getExtraData()
    {
        return $this->getData( self::EXTRA_DATA );
    }

    /**
     * @inheritDoc
     */
    public function getExtraDataArray()
    {
        if( !($extra_data_str = $this->getExtraData()) )
            return [];

        return $this->_helper->parse_string( $extra_data_str );
    }

    /**
     * @inheritDoc
     */
    public function get3DSecure()
    {
        return $this->getData( self::TDSECURE );
    }

    /**
     * @inheritDoc
     */
    public function getPaymentStatus()
    {
        return $this->getData( self::PAYMENT_STATUS );
    }

    /**
     * @inheritDoc
     */
    public function getCreated()
    {
        return $this->getData( self::CREATED );
    }

    /**
     * @inheritDoc
     */
    public function getUpdated()
    {
        return $this->getData( self::UPDATED );
    }

    /**
     * @inheritDoc
     */
    public function setID( $id )
    {
        $id = intval( $id );
        return $this->setData( self::ID, $id );
    }

    /**
     * @inheritDoc
     */
    public function setMethodID( $method_id )
    {
        $method_id = intval( $method_id );
        return $this->setData( self::METHOD_ID, $method_id );
    }

    /**
     * @inheritDoc
     */
    public function setPaymentID( $payment_id )
    {
        $payment_id = intval( $payment_id );
        return $this->setData( self::PAYMENT_ID, $payment_id );
    }

    /**
     * @inheritDoc
     */
    public function setMerchantTransactionID( $mt_id )
    {
        return $this->setData( self::MERCHANT_TRANSACTION_ID, $mt_id );
    }

    /**
     * @inheritDoc
     */
    public function setSiteID( $site_id )
    {
        $site_id = intval( $site_id );
        return $this->setData( self::SITE_ID, $site_id );
    }

    /**
     * @inheritDoc
     */
    public function setEnvironment( $environment )
    {
        $environment = trim( $environment );
        return $this->setData( self::ENVIRONMENT, $environment );
    }

    /**
     * @inheritDoc
     */
    public function setExtraData( $data )
    {
        if( is_array( $data ) )
            return $this->setExtraDataArray( $data );

        if( !is_string( $data ) )
            return $this;

        $data = trim( $data );

        return $this->setData( self::EXTRA_DATA, $data );
    }

    /**
     * @inheritDoc
     */
    public function setExtraDataArray( $data_arr )
    {
        if( !is_array( $data_arr ) )
            return $this;

        $data = $this->_helper->to_string( $data_arr );

        return $this->setData( self::EXTRA_DATA, $data );
    }

    /**
     * @inheritDoc
     */
    public function set3DSecure( $tdsecure )
    {
        return $this->setData( self::TDSECURE, $tdsecure );
    }

    /**
     * @inheritDoc
     */
    public function setPaymentStatus( $status )
    {
        return $this->setData( self::PAYMENT_STATUS, $status );
    }

    /**
     * @inheritDoc
     */
    public function setCreated( $time )
    {
        return $this->setData( self::CREATED, $time );
    }

    /**
     * @inheritDoc
     */
    public function setUpdated( $time )
    {
        return $this->setData( self::UPDATED, $time );
    }
}
