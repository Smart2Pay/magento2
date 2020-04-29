<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

class Method extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /**
     * Logger Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\LoggerFactory
     */
    private $_loggerFactory;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string|null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Smart2Pay\GlobalPay\Model\LoggerFactory $loggerFactory,
        $resourcePrefix = null
    ) {
        parent::__construct( $context, $resourcePrefix );

        $this->_s2pHelper = $s2pHelper;
        $this->_loggerFactory = $loggerFactory;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 's2p_gp_methods', 'id' );
    }

    /**
     * @inheritdoc
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
     * @param int $method_id
     * @param bool|string $environment
     * @param int $isActive
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByMethodIDSelect( $method_id, $environment = false, $isActive = null )
    {
        if( $environment === false )
            $environment = $this->_s2pHelper->getEnvironment();

        $select = parent::_getLoadSelect( 'method_id', $method_id, null );

        if( !empty( $environment ) )
            $select->where( 'environment = ?', $environment );
        if( !is_null( $isActive ) )
            $select->where( 'active = ?', (!empty( $isActive )?1:0) );

        return $select;
    }

    /**
     * Check if method_id key exists
     * return method array if method exists
     *
     * @param int $method_id
     * @param bool|string $environment
     * @return int
     */
    public function checkMethodID( $method_id, $environment = false )
    {
        if( $environment === false )
            $environment = $this->_s2pHelper->getEnvironment();

        $select = $this->_getLoadByMethodIDSelect( $method_id, $environment );

        $select->limit( 1 );

        return $this->getConnection()->fetchOne( $select );
    }

    /**
     * Check if method_id key exists
     * return method array if method exists
     *
     * @param int $method_id
     * @param bool|string $environment
     * @return array|bool
     */
    public function getByMethodID( $method_id, $environment = false )
    {
        if( $environment === false )
            $environment = $this->_s2pHelper->getEnvironment();

        $select = $this->_getLoadByMethodIDSelect( $method_id, $environment );

        $select->limit( 1 );

        if( !($result_arr = $this->getConnection()->fetchAssoc( $select ))
         or !is_array( $result_arr ) )
            return false;

        foreach( $result_arr as $key => $method_arr )
            break;

        return $method_arr;
    }

    /**
     * Check if method_id key from provided object exists
     * return method array if method exists
     *
     * @param \Smart2Pay\GlobalPay\Model\Method $object
     * @param bool|string $environment
     * @return int
     */
    public function checkObjectMethodID( \Smart2Pay\GlobalPay\Model\Method $object, $environment = false )
    {
        if( $environment === false )
            $environment = $this->_s2pHelper->getEnvironment();

        return $this->checkMethodID( $object->getMethodID(), $environment );
    }

    /**
     * @param \Smart2Pay\GlobalPay\Model\ResourceModel\Method\Collection $collection
     * @throws \Exception
     * @return bool
     */
    public function deleteFromCollection( \Smart2Pay\GlobalPay\Model\ResourceModel\Method\Collection $collection )
    {
        if( !($collection instanceof \Smart2Pay\GlobalPay\Model\ResourceModel\Method\Collection) )
            return false;

        if( !($it = $collection->getIterator()) )
            return false;

        /** @var \Smart2Pay\GlobalPay\Model\Method $item */
        foreach( $it as $item )
            $item->getResource()->delete( $item );

        return true;
    }

    /**
     * @param int $method_id
     * @param string $environment
     * @param array $params
     *
     * @return bool
     */
    public function insertOrUpdate( $method_id, $environment, $params )
    {
        $method_id = intval( $method_id );
        if( empty( $method_id )
         or empty( $params ) or !is_array( $params )
         or empty( $params['display_name'] )
         or !($conn = $this->getConnection()) )
            return false;

        if( empty( $params['description'] ) )
            $params['description'] = '';
        if( empty( $params['logo_url'] ) )
            $params['logo_url'] = '';
        if( empty( $params['guaranteed'] ) )
            $params['guaranteed'] = 0;
        if( empty( $params['active'] ) )
            $params['active'] = 0;

        $insert_arr = array();
        $insert_arr['display_name'] = $params['display_name'];
        $insert_arr['description'] = $params['description'];
        $insert_arr['logo_url'] = $params['logo_url'];
        $insert_arr['guaranteed'] = $params['guaranteed'];
        $insert_arr['active'] = $params['active'];

        try
        {
            if( ( $existing_id = $conn->fetchOne( 'SELECT id FROM ' . $this->getMainTable() . ' WHERE method_id = \'' . $method_id . '\' AND environment = \'' . $environment . '\' LIMIT 0, 1' ) ) )
            {
                // we should update record
                $conn->update( $this->getMainTable(), $insert_arr, 'id = \'' . $existing_id . '\'' );
            } else
            {
                $insert_arr['method_id']  = $method_id;
                $insert_arr['environment'] = $environment;

                $conn->insert( $this->getMainTable(), $insert_arr );

            }
        } catch( \Zend_Db_Adapter_Exception $e )
        {
            $s2pLogger = $this->_loggerFactory->create();

            $s2pLogger->write( 'DB Error ['.$e->getMessage().']', 'configured_method' );
            return false;
        }

        return true;
    }
}
