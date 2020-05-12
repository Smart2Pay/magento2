<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel\Country;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Smart2Pay\GlobalPay\Model\Country', 'Smart2Pay\GlobalPay\Model\ResourceModel\Country');
    }
}
