<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

use Magento\Framework\Exception\AbstractAggregateException;

class CountryMethod extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\CountryMethodFactory
     */
    private $_countryMethodFactory;

    /**
     * Logger Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\LoggerFactory
     */
    private $_loggerFactory;

    /** @var \Smart2Pay\GlobalPay\Model\Smart2Pay */
    protected $_s2pModel;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string|null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Smart2Pay\GlobalPay\Model\LoggerFactory $loggerFactory,
        \Smart2Pay\GlobalPay\Model\CountryMethodFactory $countryMethodFactory,
        \Smart2Pay\GlobalPay\Model\Smart2Pay $s2pModel,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);

        $this->_s2pModel = $s2pModel;
        $this->_loggerFactory = $loggerFactory;
        $this->_countryMethodFactory = $countryMethodFactory;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 's2p_gp_countries_methods', 'id' );
    }

    /**
     * @param \Smart2Pay\GlobalPay\Model\CountryMethod $object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave( \Magento\Framework\Model\AbstractModel $object )
    {
        if( ($existing_arr = $this->checkMethodCountryID( $object->getMethodID(), $object->getCountryID(), $object->getEnvironment() )) )
        {
            $this->getConnection()->delete(
                $this->getMainTable(),
                $this->getConnection()->quoteInto( $this->getIdFieldName() . '=?', $existing_arr[$this->getIdFieldName()] )
                );
        }

        return parent::_beforeSave( $object );
    }

    /**
     * Retrieve load select with filter by method_id
     *
     * @param int $method_id
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
     * @param int $country_id
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
     * Retrieve load select with filter by environment
     *
     * @param string $environment
     * @param null|\Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByEnvironmentSelect( $environment, $select = null )
    {
        if( empty( $environment ) )
            $environment = $this->_s2pModel->getEnvironment();

        if( empty( $select ) )
            $select = parent::_getLoadSelect( 'environment', $environment, null );
        else
            $select->where( 'environment = ?', $environment );

        return $select;
    }

    /**
     * Check if $method_id, $country_id pair is configured in database and return an array with details
     *
     * @param int $method_id
     * @param int $country_id
     * @param bool|string $environment
     * @return array
     */
    public function checkMethodCountryID( $method_id, $country_id, $environment = false )
    {
        $select = $this->_getLoadByMethodIDSelect( $method_id );
        $select = $this->_getLoadByCountryIDSelect( $country_id, $select );
        $select = $this->_getLoadByEnvironmentSelect( $environment, $select );

        $select->limit( 1 );

        return $this->getConnection()->fetchAssoc( $select );
    }

    /**
     * Return an array with all methods configured for a specific country
     *
     * @param int $country_id
     * @param bool|string $environment
     * @return array
     */
    public function getMethodsForCountry( $country_id, $environment = false )
    {
        $select = $this->_getLoadByCountryIDSelect( $country_id );
        $select = $this->_getLoadByEnvironmentSelect( $environment, $select );

        return $this->getConnection()->fetchAll( $select );
    }

    /**
     * Return an array with all countries configured for a specific method
     *
     * @param int $method_id
     * @param bool|string $environment
     * @return array
     */
    public function getCountriesForMethod( $method_id, $environment = false )
    {
        $select = $this->_getLoadByMethodIDSelect( $method_id );
        $select = $this->_getLoadByEnvironmentSelect( $environment, $select );

        return $this->getConnection()->fetchAll( $select );
    }

    /**
     * @param \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod\Collection $collection
     * @throws \Exception
     * @return bool
     */
    public function deleteFromCollection( \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod\Collection $collection )
    {
        if( !($collection instanceof \Smart2Pay\GlobalPay\Model\ResourceModel\CountryMethod\Collection) )
            return false;

        if( !($it = $collection->getIterator()) )
            return false;

        /** @var \Smart2Pay\GlobalPay\Model\CountryMethod $item */
        foreach( $it as $item )
            $item->getResource()->delete( $item );

        return true;
    }

    /**
     * @param int $method_id
     * @param int $country_id
     * @param string $environment
     * @param int $priority
     *
     * @throws \Exception
     *
     * @return bool|array
     */
    public function insertOrUpdate( $method_id, $country_id, $environment, $priority = 0 )
    {
        $method_id = intval( $method_id );
        $country_id = intval( $country_id );
        $environment = strtolower( trim( $environment ) );
        if( empty( $method_id ) or empty( $country_id )
         or empty( $environment ) or !in_array( $environment, array( 'demo', 'test', 'live' ) )
         or !($conn = $this->getConnection()) )
            return false;

        try
        {
            $model_obj = $this->_countryMethodFactory->create();

            if( ($country_method_arr = $conn->fetchAssoc( 'SELECT * FROM ' . $this->getMainTable() .
                                                          ' WHERE method_id = \'' . $method_id . '\' AND country_id = \'' . $country_id . '\' AND environment = \'' . $environment . '\''.
                                                          ' LIMIT 0, 1' ) ) )
            {
                $model_obj->setPriority( $priority );

                $model_obj->getResource()->save( $model_obj );

                $country_method_arr['priority'] = $priority;

                // $params = array();
                // $params['priority'] = $priority;
                //
                // // we should update record
                // $conn->update( $this->getMainTable(), $params, 'id = \'' . $country_method_arr['id'] . '\'' );

                // foreach( $params as $key => $val )
                // {
                //     if( array_key_exists( $key, $country_method_arr ) )
                //         $country_method_arr[$key] = $val;
                // }
            } else
            {
                $model_obj->setMethodID( $method_id );
                $model_obj->setCountryID( $country_id );
                $model_obj->setEnvironment( $environment );
                $model_obj->setPriority( $priority );

                $model_obj->getResource()->save( $model_obj );

                // $country_method_arr = array();
                // $country_method_arr['method_id'] = $method_id;
                // $country_method_arr['country_id'] = $country_id;
                // $country_method_arr['environment'] = $environment;
                // $country_method_arr['priority'] = $priority;
                //
                // $conn->insert( $this->getMainTable(), $country_method_arr );
                //
                // $country_method_arr['id'] = $conn->lastInsertId();

                $country_method_arr['id'] = $model_obj->getID();
            }
        } catch( \Exception $e )
        {
            $s2pLogger = $this->_loggerFactory->create();

            $s2pLogger->write( 'DB Error ['.$e->getMessage().']', 'countries_methods' );
            return false;
        }

        return $country_method_arr;
    }
}
