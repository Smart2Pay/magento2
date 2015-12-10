<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

/**
 * Class ConfiguredMethods
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods _getResource()
 * @package Smart2Pay\GlobalPay\Model\ResourceModel
 */
class ConfiguredMethods extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
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
        \Smart2Pay\GlobalPay\Model\LoggerFactory $loggerFactory,
        $resourcePrefix = null
    ) {
        $this->_loggerFactory = $loggerFactory;

        parent::__construct( $context, $resourcePrefix );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 's2p_gp_methods_configured', 'id' );
    }

    /**
     * Perform actions before object save
     *
     * @param \Smart2Pay\GlobalPay\Model\ConfiguredMethods $object
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _beforeSave( \Magento\Framework\Model\AbstractModel $object )
    {
        if( ($existing_arr = $this->checkMethodCountryID( $object->getMethodID(), $object->getCountryID() )) )
        {
            $this->getConnection()->delete(
                $this->getMainTable(),
                $this->getConnection()->quoteInto($this->getIdFieldName() . '=?', $existing_arr[$this->getIdFieldName()] )
                );
        }

        return parent::_beforeSave( $object );
    }

    /**
     * Retrieve load select with filter by method_id
     *
     * @param string $url_key
     * @param null|\Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByMethodIDSelect( $method_id, $select = null )
    {
        if( empty( $select ) )
            $select = parent::_getLoadSelect( 'method_id', $method_id, null );
        else
            $select->where( 'method_id = ?', $method_id );

        return $select;
    }

    /**
     * Retrieve load select with filter by country_id
     *
     * @param string $url_key
     * @param null|\Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByCountryIDSelect( $country_id, $select = null )
    {
        if( empty( $select ) )
            $select = parent::_getLoadSelect( 'country_id', $country_id, null );
        else
            $select->where( 'country_id = ?', $country_id );

        return $select;
    }

    /**
     * Check if $method_id, $country_id pair is configured in database and return an array with details
     *
     * @param int $method_id
     * @return int
     */
    public function checkMethodCountryID( $method_id, $country_id )
    {
        $select = $this->_getLoadByMethodIDSelect( $method_id );
        $select = $this->_getLoadByCountryIDSelect( $country_id, $select );

        $select->limit( 1 );

        return $this->getConnection()->fetchOne( $select );
    }

    /**
     * Return an array with all methods configured for a specific country
     *
     * @param int $country_id
     * @return array
     */
    public function getMethodsForCountry( $country_id )
    {
        $select = $this->_getLoadByCountryIDSelect( $country_id );

        return $this->getConnection()->fetchAll( $select );
    }

    /**
     * Return an array with all countries configured for a specific method
     *
     * @param int $method_id
     * @return array
     */
    public function getCountriesForMethod( $method_id )
    {
        $select = $this->_getLoadByMethodIDSelect( $method_id );

        return $this->getConnection()->fetchAll( $select );
    }

    /**
     * @param int $method_id
     * @param int $country_id
     * @param array $params
     *
     * @return bool
     */
    public function insert_or_update( $method_id, $country_id, $params )
    {
        $method_id = intval( $method_id );
        $country_id = intval( $country_id );
        if( empty( $method_id )
         or empty( $params ) or !is_array( $params )
         or !($conn = $this->getConnection()) )
            return false;

        if( empty( $params['surcharge'] ) )
            $params['surcharge'] = 0;
        if( empty( $params['fixed_amount'] ) )
            $params['fixed_amount'] = 0;

        $insert_arr = array();
        $insert_arr['surcharge'] = $params['surcharge'];
        $insert_arr['fixed_amount'] = $params['fixed_amount'];

        try
        {
            if( ( $existing_id = $conn->fetchOne( 'SELECT id FROM ' . $this->getMainTable() . ' WHERE method_id = \'' . $method_id . '\' AND country_id = \'' . $country_id . '\' LIMIT 0, 1' ) ) )
            {
                // we should update record
                $conn->update( $this->getMainTable(), $insert_arr, 'id = \'' . $existing_id . '\'' );
            } else
            {
                $insert_arr['method_id']  = $method_id;
                $insert_arr['country_id'] = $country_id;

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

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateTable()
    {
        $this->getConnection()->truncateTable( $this->getMainTable() );
    }

    /**
     * @param ConfiguredMethods\Collection $collection
     *
     * @return bool
     */
    public function deleteFromCollection( \Smart2Pay\GlobalPay\Model\ResourceModel\ConfiguredMethods\Collection $collection )
    {
        if( !($it = $collection->getIterator()) )
            return false;

        /** @var \Smart2Pay\GlobalPay\Model\ConfiguredMethods $item */
        foreach( $it as $item )
            $item->delete();

        return true;
    }
}
