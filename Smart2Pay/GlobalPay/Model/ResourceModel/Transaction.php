<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

class Transaction extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param string|null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 's2p_gp_transactions', 'id' );
    }

    /**
     * Process post data before saving
     *
     * @param \Smart2Pay\GlobalPay\Model\Transaction $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave( \Magento\Framework\Model\AbstractModel $object )
    {
        if( !$object->getMethodId() )
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide a method id.')
            );
        }

        if( !$object->getMerchantTransactionId() )
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide merchant transaction id.')
            );
        }

        if( ($current_id = $this->checkMerchantTransactionID( $object->getMerchantTransactionId() ))
        and $object->getID() != $current_id )
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __( 'Merchant transaction id already exists in database.' )
            );
        }

        $time = time();

        if( !$object->getPaymentID() )
            $object->setPaymentID( 0 );
        if( !$object->getSiteId() )
            $object->setSiteId( 0 );
        if( !$object->getPaymentStatus() )
            $object->setPaymentStatus( 0 );
        if( !$object->getCreated() )
            $object->setCreated( $time );

        $object->setUpdated( $time );

        return parent::_beforeSave( $object );
    }

    /**
     * Retrieve load select with filter by merchant transaction idcode
     *
     * @param string $code
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByMerchantTransactionId( $mt_id )
    {
        $select = parent::_getLoadSelect( 'merchant_transaction_id', $mt_id, null );

        return $select;
    }

    /**
     * Check if merchant transaction id key exists
     * return transaction object if merchant transaction id exists
     *
     * @param int $method_id
     * @return int
     */
    public function checkMerchantTransactionId( $mt_id )
    {
        $select = $this->_getLoadByMerchantTransactionId( $mt_id );

        $select->limit( 1 );

        return $this->getConnection()->fetchOne( $select );
    }
}
