<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel\Logger;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Smart2Pay\GlobalPay\Model\Logger', 'Smart2Pay\GlobalPay\Model\ResourceModel\Logger');
    }
}
