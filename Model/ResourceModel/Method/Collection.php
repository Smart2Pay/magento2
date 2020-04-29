<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel\Method;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Smart2Pay\GlobalPay\Model\Method', 'Smart2Pay\GlobalPay\Model\ResourceModel\Method' );
    }

}

