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
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'configured_methods.phtml';

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

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
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        array $data = []
    )
    {
        $this->_s2pHelper = $s2pHelper;
        $this->_methodFactory = $methodFactory;
        $this->_countryMethodFactory = $countryMethodFactory;
        $this->_configuredMethodsFactory = $configuredMethodsFactory;

        parent::__construct( $context, $data );
    }

    public function getBaseCurrency()
    {
        if( !empty( $this->_base_currency ) )
            return $this->_base_currency;

        $base_currency = $this->_s2pHelper->getBaseCurrencies();
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

    public function get_environment()
    {
        return $this->_s2pHelper->getEnvironment();
    }

    public function get_sdk_version()
    {
        $sdk_helper = $this->_s2pHelper->getSDKHelper();
        return $sdk_helper::get_sdk_version();
    }

    public function get_last_sync_date()
    {
        return $this->_s2pHelper->last_methods_sync_option();
    }

    public function seconds_to_launch_sync_str()
    {
        return $this->_s2pHelper->seconds_to_launch_sync_str();
    }

    public function getAllActiveMethods( $environment = false, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['include_countries'] ) )
            $params['include_countries'] = false;

        return $this->_methodFactory->create()->getAllActiveMethods( $environment, $params );
    }

    /**
     * @param bool|array $params
     * @param bool|string $environment
     *
     * @return array
     */
    public function getAllConfiguredMethods( $environment = false, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['only_active'] ) )
            $params['only_active'] = true;

        return $this->_configuredMethodsFactory->create()->getAllConfiguredMethods( $environment, $params );
    }
}
