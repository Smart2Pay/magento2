<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Smart2Pay\GlobalPay\Gateway\Request\MockDataRequest;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /**
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->_s2pHelper = $s2pHelper;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $s2p_helper = $this->_s2pHelper;

        return $this->transferBuilder
            ->setBody($request)
            ->build();
    }
}
