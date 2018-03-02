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
     * Asset service
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Smart2Pay\GlobalPay\Model\Smart2Pay
     */
    protected $method_instance = false;

    /**
     * @var Escaper
     */
    //protected $escaper = null;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        //Escaper $escaper,
        \Magento\Framework\View\Element\Context $context,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory
    ) {
        $this->_configuredMethodFactory = $configuredMethodsFactory;
        //$this->escaper = $escaper;

        $this->_request = $context->getRequest();
        $this->_assetRepo = $context->getAssetRepository();

        try {
            $this->method_instance = $paymentHelper->getMethodInstance( Smart2Pay::METHOD_CODE );
        } catch( \Magento\Framework\Exception\LocalizedException $e )
        {
            $this->method_instance = false;
        }
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->_request->isSecure()], $params);
            return $this->_assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $configured_methods = $this->_configuredMethodFactory->create();

        if( empty( $this->method_instance )
         or !$this->method_instance->isAvailable() )
            return array();

        $config = array();
        $config['payment'][Smart2Pay::METHOD_CODE]['settings'] = $this->method_instance->getFrontConfigArray();
        $config['payment'][Smart2Pay::METHOD_CODE]['images_url'] = $this->getViewFileUrl( 'Smart2Pay_GlobalPay::images' );
        $config['payment'][Smart2Pay::METHOD_CODE]['methods'] = $configured_methods->getAllConfiguredMethodsPerCountryCode( $this->method_instance->getEnvironment() );

        return $config;
    }
}
