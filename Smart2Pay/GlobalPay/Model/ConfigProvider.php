<?php
namespace Smart2Pay\GlobalPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Smart2Pay\GlobalPay\Model\ConfiguredMethods;

class ConfigProvider implements ConfigProviderInterface
{

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory
     */
    private $_configuredMethodFactory;

    /**
     * @var string[]
     */
    protected $methodCodes = [
        Smart2Pay::METHOD_CODE,
    ];

    /**
     * @var \Smart2Pay\GlobalPay\Model\Smart2Pay[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory
    ) {
        $this->_configuredMethodFactory = $configuredMethodsFactory;
        $this->escaper = $escaper;
        foreach( $this->methodCodes as $code )
        {
            $this->methods[$code] = $paymentHelper->getMethodInstance( $code );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $configured_methods = $this->_configuredMethodFactory->create();

        $config = [];
        foreach( $this->methodCodes as $code )
        {
            if( $this->methods[$code]->isAvailable() )
                $config['payment'][$code]['settings'] = $this->methods[$code]->getFrontConfigArray();
        }

        $config['payment'][Smart2Pay::METHOD_CODE]['methods'] = $configured_methods->getAllConfiguredMethodsPerCountryCode();

        return $config;
    }
}
