<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

class Method extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init( 's2p_gp_methods', 'method_id' );
    }

    /**
     * Process post data before saving
     *
     * @param \Smart2Pay\GlobalPay\Model\Method $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave( \Magento\Framework\Model\AbstractModel $object )
    {
        if( $this->checkObjectMethodID( $object ) )
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __( 'Method ID already exists in database.' )
            );
        }

        return parent::_beforeSave( $object );
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param \Smart2Pay\GlobalPay\Model\Method $object
     * @return \Zend_Db_Select
     */
    protected function _getLoadSelect( $field, $value, $object = null )
    {
        $select = parent::_getLoadSelect( $field, $value, $object );

        $select->where(
            'active = ?',
            1
        )->limit(
            1
        );

        return $select;
    }

    /**
     * Retrieve load select with filter by url_key and activity
     *
     * @param string $url_key
     * @param int $isActive
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByMethodIDSelect( $method_id, $isActive = null )
    {
        $select = parent::_getLoadSelect( 'method_id', $method_id, null );

        if( !is_null( $isActive ) )
            $select->where( 'active = ?', (!empty( $isActive )?1:0) );

        return $select;
    }

    /**
     * Check if method_id key exists
     * return method array if method exists
     *
     * @param int $method_id
     * @return int
     */
    public function checkMethodID( $method_id )
    {
        $select = $this->_getLoadByMethodIDSelect( $method_id );

        $select->limit(1);

        return $this->getConnection()->fetchOne( $select );
    }

    /**
     * Check if method_id key exists
     * return method array if method exists
     *
     * @param int $method_id
     * @return int
     */
    public function checkObjectMethodID( \Smart2Pay\GlobalPay\Model\Method $object )
    {
        return $this->checkMethodID( $object->getMethodID() );
    }
}
