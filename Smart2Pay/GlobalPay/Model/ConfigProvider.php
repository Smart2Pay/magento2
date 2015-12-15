<?php
namespace Smart2Pay\GlobalPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
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
        Escaper $escaper
    ) {
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
        $config = [];
        foreach( $this->methodCodes as $code )
        {
            if( $this->methods[$code]->isAvailable() )
                $config['payment'][$code] = $this->methods[$code]->getFullConfigArray();
        }

        return $config;
    }
    //
    ///**
    // * Get instructions text from config
    // *
    // * @param string $code
    // * @return string
    // */
    //protected function getInstructions($code)
    //{
    //    return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    //}
}
