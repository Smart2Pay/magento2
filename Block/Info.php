<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;

class Info extends ConfigurableInfo
{
    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var \Smart2Pay\GlobalPay\Model\MethodFactory */
    protected $_s2pMethod;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper
     * @param \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory,
        \Smart2Pay\GlobalPay\Model\MethodFactory $methodsFactory,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);

        $this->_s2pHelper = $s2pHelper;
        $this->_s2pTransaction = $transactionFactory;
        $this->_s2pMethod = $methodsFactory;
    }

    /**
     * Prepare payment information
     *
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);

        $helper_obj = $this->_s2pHelper;
        $s2p_transaction = $this->_s2pTransaction->create();
        $s2p_method = $this->_s2pMethod->create();

        $is_in_frontend = $this->getIsSecureMode();
        $payment = $this->getInfo();

        if (!($extra_info = $payment->getAdditionalInformation())
        || !is_array($extra_info)
        || empty($extra_info['sp_transaction']) ) {
            return $transport;
        }

        $s2p_transaction->load($extra_info['sp_transaction']);

        if (empty($extra_info['sp_method'])
        || !($method_arr = $s2p_method->getByMethodID($extra_info['sp_method'])) ) {
            $method_arr = [];
        }

        $this->setDataToTransfer(
            $transport,
            __('Payment Method'),
            $method_arr['display_name'].(!$is_in_frontend?' (#'.$method_arr['method_id'].')':'')
        );

        if (!$is_in_frontend) {
            $this->setDataToTransfer(
                $transport,
                __('3DSecure'),
                ($s2p_transaction->get3DSecure()? __('Yes') : __('No'))
            );
            $this->setDataToTransfer(
                $transport,
                __('Environment'),
                $s2p_transaction->getEnvironment()
            );
            $this->setDataToTransfer(
                $transport,
                __('PaymentID'),
                $s2p_transaction->getPaymentId()
            );
            $this->setDataToTransfer(
                $transport,
                __('SiteID'),
                $s2p_transaction->getSiteId()
            );
        }

        if (($extra_trans_data = $s2p_transaction->getExtraDataArray())
        && is_array($extra_trans_data) ) {
            if (!($titles_arr = $helper_obj::getTransactionReferenceTitles())) {
                $titles_arr = [];
            }

            foreach ($extra_trans_data as $key => $val) {
                if (!empty($titles_arr[$key])) {
                    $title_txt = $titles_arr[$key];
                } else {
                    $title_txt = __($key);
                }

                $this->setDataToTransfer(
                    $transport,
                    $title_txt,
                    $val
                );
            }
        }

        // foreach( $extra_info as $field => $val )
        // {
        //     $this->setDataToTransfer(
        //         $transport,
        //         $field,
        //         $val
        //     );
        //
        // }

        return $transport;
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return string|Phrase
     */
    protected function getLabel($field)
    {
        return $field;
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        return $value;//parent::getValueView($field, $value);
    }
}
