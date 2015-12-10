<?php

namespace Smart2Pay\GlobalPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Directory\Model;

class Smart2Pay extends AbstractHelper
{
    /**
     * Currency Factory
     *
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    ) {
        $this->_currencyFactory = $currencyFactory;
        parent::__construct( $context );
    }

    public function getBaseCurrencies()
    {
        $currency = $this->_currencyFactory->create();
        return $currency->getConfigBaseCurrencies();
    }

    /**
     * Retrieve param by key
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getParam( $key, $defaultValue = null )
    {
        return $this->_request->getParam( $key, $defaultValue );
    }

}
