<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'smart2pay';

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /** @var \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory */
    private $_configuredMethodFactory;

    /** @var \Magento\Framework\View\Asset\Repository */
    protected $_assetRepo;

    /** @var \Magento\Framework\App\RequestInterface */
    protected $_request;

    public function __construct(
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Magento\Framework\View\Element\Context $context,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory
    ) {
        $this->_s2pHelper = $s2pHelper;
        $this->_configuredMethodFactory = $configuredMethodsFactory;

        $this->_request = $context->getRequest();
        $this->_assetRepo = $context->getAssetRepository();
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
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $s2p_helper = $this->_s2pHelper;
        $configured_methods = $this->_configuredMethodFactory->create();

        return [
            'payment' => [
                $s2p_helper::METHOD_CODE => [
                    'environment' => $s2p_helper->getEnvironment(),
                    'settings' => $s2p_helper->getFrontConfigArray(),
                    'methods' => $configured_methods->getAllConfiguredMethodsPerCountryCode(
                        $s2p_helper->getEnvironment()
                    ),
                ]
            ]
        ];
    }
}
