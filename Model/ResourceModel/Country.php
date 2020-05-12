<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

class Country extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
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
        $this->_init('s2p_gp_countries', 'country_id');
    }

    /**
     * Process post data before saving
     *
     * @param \Smart2Pay\GlobalPay\Model\Country $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!$object->getCode()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide country code.')
            );
        }

        if (!$object->getName()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide country name.')
            );
        }

        if ($this->checkCode($object)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Country code already exists in database.')
            );
        }

        return parent::_beforeSave($object);
    }

    /**
     * Retrieve load select with filter by country code
     *
     * @param string $code
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadByCodeSelect($code)
    {
        $select = parent::_getLoadSelect('code', $code, null);

        return $select;
    }

    /**
     * Check if code key exists
     * return country id if method exists
     *
     * @param string $code
     * @return int
     */
    public function checkCode($code)
    {
        $select = $this->_getLoadByCodeSelect($code);

        $select->limit(1);

        return $this->getConnection()->fetchOne($select);
    }
}
