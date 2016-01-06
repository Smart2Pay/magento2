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

    public function s2p_mb_substr( $message, $start, $length )
    {
        if( function_exists( 'mb_substr' ) )
            return mb_substr( $message, $start, $length, 'UTF-8' );
        else
            return substr( $message, $start, $length );
    }

    public function s2p_mb_strtolower( $message )
    {
        if( function_exists( 'mb_strtolower' ) )
            return mb_strtolower( $message, 'UTF-8' );
        else
            return strtolower( $message );
    }

    public function computeSHA256Hash( $message )
    {
        return hash( 'sha256', $this->s2p_mb_strtolower( $message ) );
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
