<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Payment;
use Smart2Pay\GlobalPay\Model\Country;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const SELECTED_COUNTRY = 'selected_country';
    const S2P_METHOD = 'sp_method';

    protected static $DETAILS_ARR = [
        self::SELECTED_COUNTRY,
        self::S2P_METHOD,
    ];

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /** @var \Smart2Pay\GlobalPay\Model\Country */
    protected $_country;

    /** @var \Smart2Pay\GlobalPay\Model\ConfiguredMethods */
    protected $_configuredMethods;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var CheckoutSession */
    protected $_checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_s2pHelper = $s2pHelper;
        $this->_s2pTransaction = $transactionFactory;
        $this->_configuredMethods = $configuredMethodsFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)
         || empty($additionalData['selected_country'])
         || !($country_code = strtoupper(trim($additionalData['selected_country'])))
         || empty($additionalData['sp_method'])
         || !($s2p_method = (int)$additionalData['sp_method'])) {
            return;
        }

        /** @var Payment $paymentModel */
        $paymentModel = $this->readPaymentModelArgument($observer);
        if (!$paymentModel instanceof Payment) {
            return;
        }

        $quote = $paymentModel->getQuote();

        $environment = $this->_s2pHelper->getEnvironment();
        $configured_methods_instance = $this->_configuredMethods->create();

        if (!($method_details = $configured_methods_instance->getConfiguredMethodDetails(
            $s2p_method,
            $environment,
            [ 'country_code' => $country_code, 'only_active' => true ]
        ))) {
            return;
        }

        $details_arr = [];
        if (!empty($method_details[$country_code])) {
            $details_arr = $method_details[$country_code];
        } elseif (!empty($method_details[Country::INTERNATIONAL_CODE])) {
            $details_arr = $method_details[Country::INTERNATIONAL_CODE];
        }

        if (empty($details_arr) || !is_array($details_arr)) {
            return;
        }

        // $s2p_transaction = $this->_s2pTransaction->create();
        //
        // $s2p_transaction
        //     ->setMethodID( $s2p_method )
        //     ->setEnvironment( $environment )
        //     ->setMerchantTransactionID( 'NOTSETYET_'.microtime( true ) );
        //
        // $s2p_transaction->getResource()->save( $s2p_transaction );

        $paymentModel->setAdditionalInformation('environment', $environment);
        $paymentModel->setAdditionalInformation('country', $additionalData['selected_country']);
        $paymentModel->setAdditionalInformation('sp_method', $s2p_method);
        $paymentModel->setAdditionalInformation(
            'sp_surcharge',
            (isset($details_arr['surcharge'])?$details_arr['surcharge']:0)
        );
        $paymentModel->setAdditionalInformation(
            'sp_fixed_amount',
            (isset($details_arr['fixed_amount'])?$details_arr['fixed_amount']:0)
        );
        //$paymentModel->setAdditionalInformation( 'sp_transaction', $s2p_transaction->getID() );

        if (!empty($details_arr['surcharge'])
         || !empty($details_arr['fixed_amount'])) {
            $paymentModel->setS2pSurchargePercent($details_arr['surcharge']);

            if (($total_amount = $quote->getGrandTotal())) {
                $total_amount -= ($paymentModel->getS2pSurchargeAmount() + $paymentModel->getS2pSurchargeFixedAmount());
            }
            if (($total_base_amount = $quote->getBaseGrandTotal())) {
                $total_base_amount -= ($paymentModel->getS2pSurchargeBaseAmount()
                                       + $paymentModel->getS2pSurchargeFixedBaseAmount());
            }

            $surcharge_amount = 0.0;
            if (!empty($total_amount)
            && (float)$details_arr['surcharge'] !== 0.0) {
                $surcharge_amount = (float)($total_amount * $details_arr['surcharge']) / 100;
            }
            $surcharge_base_amount = 0;
            if (!empty($total_base_amount)
            && (float)$details_arr['surcharge'] !== 0.0) {
                $surcharge_base_amount = (float)($total_base_amount * $details_arr['surcharge']) / 100;
            }

            $surcharge_fixed_amount = 0.0;
            $surcharge_fixed_base_amount = 0.0;
            if ((float)$details_arr['fixed_amount'] !== 0.0) {
                $surcharge_fixed_base_amount = (float)$details_arr['fixed_amount'];
            }

            if ($surcharge_fixed_base_amount !== 0.0) {
                $surcharge_fixed_amount = $quote->getStore()->getBaseCurrency()->convert(
                    $surcharge_fixed_base_amount,
                    $quote->getQuoteCurrencyCode()
                );
            }

            //$logger_obj->write( 'Total ['.$total_amount.'] Base ('.$total_base_amount.'), '.
            //                    'SurchargeFixed ['.$surcharge_fixed_amount.'] '.
            //                    'BaseFixed ('.$surcharge_fixed_base_amount.'), '.
            //                    'Surcharge ['.$surcharge_amount.'] Base ('.$surcharge_base_amount.') '.
            //                    ' ['.$details_arr['surcharge'].'%]' );

            $paymentModel->setS2pSurchargeAmount($surcharge_amount);
            $paymentModel->setS2pSurchargeBaseAmount($surcharge_base_amount);
            $paymentModel->setS2pSurchargeFixedAmount($surcharge_fixed_amount);
            $paymentModel->setS2pSurchargeFixedBaseAmount($surcharge_fixed_base_amount);

            // Recollect totals for surcharge amount
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
        }
    }
}
