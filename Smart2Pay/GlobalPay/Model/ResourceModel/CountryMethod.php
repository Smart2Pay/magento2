<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

class CountryMethod extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init( 's2p_gp_countries_methods', 'id' );
    }

    /**
     * Perform actions before object save
     *
     * @param \Smart2Pay\GlobalPay\Model\CountryMethod $object
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
}
