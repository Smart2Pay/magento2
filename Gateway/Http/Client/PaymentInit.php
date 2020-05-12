<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Gateway\Http\Client;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Checkout\Model\Session as CheckoutSession;

class PaymentInit implements ClientInterface
{
    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /** @var \Smart2Pay\GlobalPay\Model\Logger */
    protected $_s2p_logger;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var CheckoutSession */
    protected $_checkoutSession;

    /** @var  \Magento\Framework\Mail\Template\TransportBuilder */
    private $_transportBuilder;

    /** @var Logger */
    private $logger;

    /**
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper
     * @param \Smart2Pay\GlobalPay\Model\LoggerFactory $s2pLogger
     * @param \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory
     * @param Logger $logger
     */
    public function __construct(
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Smart2Pay\GlobalPay\Model\LoggerFactory $s2pLogger,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory,
        CheckoutSession $checkoutSession,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        Logger $logger
    ) {
        $this->_s2pHelper = $s2pHelper;
        $this->_s2p_logger = $s2pLogger;
        $this->_s2pTransaction = $transactionFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_transportBuilder = $transportBuilder;
        $this->logger = $logger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $s2p_helper = $this->_s2pHelper;
        $s2p_logger = $this->_s2p_logger->create();

        $response_flow_arr = $s2p_helper::defaultPaymentResponseFlow();

        if (!($request_flow_arr = $transferObject->getBody())
         || !($request_flow_arr = $s2p_helper::validatePaymentRequestFlow($request_flow_arr))) {
            $response_flow_arr['errors'][] = __('Couldn\'t obtain payment flow. Please try again.');

            return $response_flow_arr;
        }

        if (empty($request_flow_arr['payment'])) {
            $response_flow_arr['errors'][] = __('Couldn\'t obtain payment instance. Please try again.');

            return $response_flow_arr;
        }

        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $request_flow_arr['payment'];

        $request_flow_arr['payment'] = false;

        if (!($config_arr = $s2p_helper->getFullConfigArray())) {
            $response_flow_arr['errors'][] = __('Couldn\'t obtain module configuration. Please contact support.');

            return $response_flow_arr;
        }

        if (empty($request_flow_arr['payload']) || !is_array($request_flow_arr['payload'])) {
            $response_flow_arr['errors'][] = __('Couldn\'t obtain payment payload. Please try again.');

            return $response_flow_arr;
        }

        if (empty($request_flow_arr['s2p_method'])) {
            $response_flow_arr['errors'][] = __('Couldn\'t obtain Smart2Pay payment method. Please try again.');

            return $response_flow_arr;
        }

        $sdk_obj = $s2p_helper->getSDKHelper();
        $api_credentials = $s2p_helper->getAPICredentials();

        $method_arr = $request_flow_arr['s2p_method'];
        $payment_arr = $request_flow_arr['payload'];
        $method_id = (int)$payment_arr['methodid'];

        $response_flow_arr['s2p_method'] = $method_arr;

        if ($s2p_helper->isCardMethod($method_id)) {
            if ($s2p_helper->has3DSecure($method_id)) {
                if (!isset($method_arr['3dsecure'])) {
                    $per_country_3dsecure = -1;
                } else {
                    $per_country_3dsecure = (int)$method_arr['3dsecure'];
                }

                if (($per_country_3dsecure === -1 && $config_arr['use_3dsecure'])
                    || $per_country_3dsecure === 1) {
                    $payment_arr['3dsecure'] = true;
                } else {
                    $payment_arr['3dsecure'] = false;
                }
            } else {
                $payment_arr['3dsecure'] = false;
            }

            if (!($payment_request = $sdk_obj->cardInitPayment($payment_arr))) {
                if (!$sdk_obj->hasError()) {
                    $error_msg = 'Couldn\'t initiate request to server.';
                } else {
                    $error_msg = 'Call error: '.strip_tags($sdk_obj->getError());
                }

                $s2p_logger->write($error_msg, 'SDK_payment_error');

                $response_flow_arr['errors'][] = $error_msg;

                return $response_flow_arr;
            }
        } elseif (!($payment_request = $sdk_obj->initPayment($payment_arr))) {
            if (!$sdk_obj->hasError()) {
                $error_msg = 'Couldn\'t initiate request to server.';
            } else {
                $error_msg = 'Call error: '.strip_tags($sdk_obj->getError());
            }

            $s2p_logger->write($error_msg, 'SDK_payment_error');

            $response_flow_arr['errors'][] = $error_msg;

            return $response_flow_arr;
        }

        $response_flow_arr['response'] = $payment_request;

        $redirect_parameters = [];
        $redirect_parameters['_query'] = [];

        $redirect_to_payment = true;
        if ($method_id === $s2p_helper::PAYMENT_METHOD_BT || $method_id === $s2p_helper::PAYMENT_METHOD_SIBS) {
            $redirect_to_payment = false;
        }

        $extra_data_arr = [];
        if (!empty($payment_request['referencedetails']) && is_array($payment_request['referencedetails'])) {
            // Hack for methods that should return amount to pay
            if (($method_id === $s2p_helper::PAYMENT_METHOD_BT || $method_id === $s2p_helper::PAYMENT_METHOD_SIBS)
            && empty($payment_request['referencedetails']['amounttopay'])) {
                $redirect_to_payment = true;

                $account_currency = false;
                if (!empty($payment_request['referencedetails']['accountcurrency'])) {
                    $account_currency = $payment_request['referencedetails']['accountcurrency'];
                } elseif ($method_id === $s2p_helper::PAYMENT_METHOD_SIBS) {
                    $account_currency = 'EUR';
                }

                if ($account_currency
                && strtolower($payment_arr['currency']) === strtolower($account_currency)) {
                    $payment_request['referencedetails']['amounttopay'] =
                        number_format(
                            $payment_arr['amount']/100,
                            2,
                            '.',
                            ''
                        ).' '.$payment_arr['currency'];
                    $redirect_to_payment = false;
                }
            }

            foreach ($payment_request['referencedetails'] as $key => $val) {
                if ($val === null) {
                    continue;
                }

                $redirect_parameters['_query'][$key] = $val;
                $extra_data_arr[$key] = $val;
            }
        }

        $payment_status = ((!empty($payment_request['status']) && !empty($payment_request['status']['id']))?
            $payment_request['status']['id']:0);

        $s2p_transaction = $this->_s2pTransaction->create();

        $s2p_transaction
            ->setMethodID($method_id)
            ->setMerchantTransactionID($payment_arr['merchanttransactionid'])
            ->setSiteID($api_credentials['site_id'])
            ->setEnvironment($config_arr['environment'])
            ->setExtraDataArray($extra_data_arr)
            ->set3DSecure((!empty($payment_arr['3dsecure'])?1:0))
            ->setPaymentStatus($payment_status)
        ;

        if (!empty($payment_request['id'])) {
            $s2p_transaction->setPaymentID($payment_request['id']);
        }

        try {
            $s2p_transaction->save();
        } catch (\Exception $e) {
            $s2p_logger->write(
                'Failed saving transaction details ['.$payment_arr['merchanttransactionid'].':'.$e->getMessage().']',
                'SDK_payment_error',
                $payment_arr['merchanttransactionid']
            );

            $response_flow_arr['errors'][] = __('Failed saving transaction for order. Please try again.');

            return $response_flow_arr;
        }

        if (empty($payment_request['redirecturl'])) {
            $s2p_logger->write(
                'Redirect URL not provided in API response. Please try again. ['.
                $payment_arr['merchanttransactionid'].']',
                'SDK_payment_error',
                $payment_arr['merchanttransactionid']
            );

            $response_flow_arr['errors'][] = __('Redirect URL not provided in API response. Please try again.');

            return $response_flow_arr;
        }

        $payment->setAdditionalInformation('sp_transaction', $s2p_transaction->getID());
        $payment->setAdditionalInformation('sp_payment_id', $s2p_transaction->getPaymentId());
        $payment->setAdditionalInformation('sp_redirect_url', $payment_request['redirecturl']);
        $payment->setAdditionalInformation('sp_do_redirect', $redirect_to_payment);

        if (!empty($config_arr['notify_payment_instructions'])
        && !empty($extra_data_arr)
        && in_array(
            $method_id,
            [ $s2p_helper::PAYMENT_METHOD_BT, $s2p_helper::PAYMENT_METHOD_SIBS ],
            true
        )
        && ($quote_obj = $this->_checkoutSession->getQuote())) {
            // send payment instructions email...
            $this->sendPaymentDetails($method_id, $quote_obj, $payment_arr['merchanttransactionid'], $extra_data_arr);
        }

        return $response_flow_arr;
    }

    /**
     * Send email with payment details to customer
     *
     * @param int $method_id
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $transaction_id
     * @param array $payment_details_arr Payment details
     *
     * @return bool True if success, false if failed
     */
    public function sendPaymentDetails(
        $method_id,
        \Magento\Quote\Model\Quote $quote,
        $transaction_id,
        $payment_details_arr
    ) {
        $helper_obj = $this->_s2pHelper;
        $s2p_logger = $this->_s2p_logger->create();

        $payment_details_arr = $helper_obj::validateTransactionReferenceValues($payment_details_arr);

        if ($quote->getCustomerFirstname()) {
            $customerName = $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname();
        } else {
            $customerName = (string)__('Guest');
        }

        try {
            if (!($order_increment_id = $quote->getReservedOrderId())
             || !($method_config = $helper_obj->getFullConfigArray(false, $quote->getStoreId()))
             || empty($method_id)
             || !in_array(
                 $method_id,
                 [ $helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS ],
                 true
             )) {
                return false;
            }

            $siteUrl = $quote->getStore()->getBaseUrl();
            $siteName = $helper_obj->getStoreName($quote->getStoreId());

            $supportEmail = $helper_obj->getStoreConfig('trans_email/ident_support/email', $quote->getStoreId());
            $supportName = $helper_obj->getStoreConfig('trans_email/ident_support/name', $quote->getStoreId());

            if ($method_id === $helper_obj::PAYMENT_METHOD_SIBS) {
                $templateId = $method_config['smart2pay_email_payment_instructions_sibs'];
            } else {
                $templateId = $method_config['smart2pay_email_payment_instructions_bt'];
            }

            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $customerName;
            $payment_details_arr['order_date'] = $helper_obj->formatDate(
                $quote->getCreatedAt(),
                \IntlDateFormatter::LONG,
                $quote->getStoreId()
            );
            $payment_details_arr['support_email'] = $supportEmail;

            //$this->inlineTranslation->suspend();

            $transport = $this->_transportBuilder->setTemplateIdentifier($templateId)
                                                 ->setTemplateOptions(
                                                     [
                                                         'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                                                         'store' => $quote->getStoreId()
                                                     ]
                                                 )
                                                 ->setTemplateVars($payment_details_arr)
                                                 ->setFrom(['name' => $supportName, 'email' => $supportEmail ])
                                                 ->addTo($quote->getCustomerEmail())
                                                 ->getTransport();
            $transport->sendMessage();

            //$this->inlineTranslation->resume();
        } catch (\Magento\Framework\Exception\MailException $e) {
            $s2p_logger->write(
                'Error sending payment instructions email to ['.$quote->getCustomerEmail().']',
                'email_template',
                $transaction_id
            );
            $s2p_logger->write($e->getMessage(), 'email_exception', $transaction_id);
        } catch (\Exception $e) {
            $s2p_logger->write($e->getMessage(), 'exception', $transaction_id);
        }

        return true;
    }
}
