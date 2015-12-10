<?php

namespace Smart2Pay\GlobalPay\Block\Adminhtml\System\Config\Field;

class ConfiguredMethods extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\MethodFactory
     */
    private $_methodFactory;

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\CountryMethodFactory
     */
    private $_countryMethodFactory;

    /**
     * Configured Methods Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory
     */
    private $_configuredMethodsFactory;

    /**
     * Helper
     *
     * @var \Smart2Pay\GlobalPay\Helper\Smart2Pay
     */
    protected $_helper;

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'configured_methods.phtml';

    private $_base_currency = '';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smart2Pay\GlobalPay\Model\MethodFactory $methodFactory,
        \Smart2Pay\GlobalPay\Model\CountryMethodFactory $countryMethodFactory,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory,
        \Smart2Pay\GlobalPay\Helper\Smart2Pay $helperSmart2Pay,
        array $data = []
    )
    {
        $this->_methodFactory = $methodFactory;
        $this->_countryMethodFactory = $countryMethodFactory;
        $this->_configuredMethodsFactory = $configuredMethodsFactory;

        $this->_helper = $helperSmart2Pay;

        parent::__construct( $context, $data );
    }

    public function getBaseCurrency()
    {
        if( !empty( $this->_base_currency ) )
            return $this->_base_currency;

        $base_currency = $this->_helper->getBaseCurrencies();
        if( !empty( $base_currency ) and is_array( $base_currency )
        and !empty( $base_currency[0] ) )
            $this->_base_currency = $base_currency[0];

        return $this->_base_currency;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setRenderer( $this );
        return $this->_toHtml();
    }

    public function getAllActiveMethods( $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['include_countries'] ) )
            $params['include_countries'] = false;

        return $this->_methodFactory->create()->getAllActiveMethods( $params );
    }

    /**
     * @param bool|array $params
     *
     * @return array
     */
    public function getAllConfiguredMethods( $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        return $this->_configuredMethodsFactory->create()->getAllConfiguredMethods( $params );
    }
}
