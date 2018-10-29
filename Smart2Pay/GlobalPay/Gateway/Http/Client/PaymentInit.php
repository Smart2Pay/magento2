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

class PaymentInit implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /** @var \Smart2Pay\GlobalPay\Model\Logger */
    protected $_s2p_logger;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

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
        Logger $logger
    ) {
        $this->_s2pHelper = $s2pHelper;
        $this->_s2p_logger = $s2pLogger;
        $this->_s2pTransaction = $transactionFactory;
        $this->logger = $logger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest( TransferInterface $transferObject )
    {
        $s2p_helper = $this->_s2pHelper;
        $s2p_logger = $this->_s2p_logger->create();

        $response_flow_arr = $s2p_helper::default_payment_response_flow();

        if( !($request_flow_arr = $transferObject->getBody())
         or !($request_flow_arr = $s2p_helper::validate_payment_request_flow( $request_flow_arr )) )
        {
            $response_flow_arr['errors'][] = __( 'Couldn\'t obtain payment flow. Please try again.' );

            return $response_flow_arr;
        }

        if( empty( $request_flow_arr['payment'] ) )
        {
            $response_flow_arr['errors'][] = __( 'Couldn\'t obtain payment instance. Please try again.' );

            return $response_flow_arr;
        }

        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $request_flow_arr['payment'];

        $request_flow_arr['payment'] = false;

        ob_start();
        echo 'PaymentInit';
        echo $s2p_helper::var_dump( $request_flow_arr, array( 'max_level' => 5 ) );
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        if( !($config_arr = $s2p_helper->getFullConfigArray()) )
        {
            ob_start();
            echo 'PaymentInit pula config';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            $response_flow_arr['errors'][] = __( 'Couldn\'t obtain module configuration. Please contact support.' );

            return $response_flow_arr;
        }

        if( empty( $request_flow_arr['payload'] ) or !is_array( $request_flow_arr['payload'] ) )
        {
            ob_start();
            echo 'PaymentInit pula payload';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            $response_flow_arr['errors'][] = __( 'Couldn\'t obtain payment payload. Please try again.' );

            return $response_flow_arr;
        }

        if( empty( $request_flow_arr['s2p_method'] ) )
        {
            ob_start();
            echo 'PaymentInit pula method';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            $response_flow_arr['errors'][] = __( 'Couldn\'t obtain Smart2Pay payment method. Please try again.' );

            return $response_flow_arr;
        }

        ob_start();
        echo 'PaymentInit ALL GOOD';
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        $sdk_obj = $s2p_helper->getSDKHelper();
        $api_credentials = $s2p_helper->get_api_credentials();

        $method_arr = $request_flow_arr['s2p_method'];
        $payment_arr = $request_flow_arr['payload'];
        $method_id = $payment_arr['methodid'];

        $response_flow_arr['s2p_method'] = $method_arr;

        if( $s2p_helper->is_card_method( $method_id ) )
        {
            if( $s2p_helper->has_3d_secure( $method_id ) )
            {
                if( !isset( $method_arr['3dsecure'] ) )
                    $per_country_3dsecure = -1;
                else
                    $per_country_3dsecure = intval( $method_arr['3dsecure'] );

                if( ($per_country_3dsecure == -1 and $config_arr['use_3dsecure'])
                    or $per_country_3dsecure == 1 )
                    $payment_arr['3dsecure'] = true;
                else
                    $payment_arr['3dsecure'] = false;
            } else
                $payment_arr['3dsecure'] = false;

            ob_start();
            echo 'PaymentInit CARD INIT';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            if( !($payment_request = $sdk_obj->card_init_payment( $payment_arr )) )
            {
                if( !$sdk_obj->has_error() )
                    $error_msg = 'Couldn\'t initiate request to server.';
                else
                    $error_msg = 'Call error: '.strip_tags( $sdk_obj->get_error() );

                $s2p_logger->write( $error_msg, 'SDK_payment_error' );

                $response_flow_arr['errors'][] = $error_msg;

                return $response_flow_arr;
            }
        } else
        {
            ob_start();
            echo 'PaymentInit PAYMENT INIT';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            if( !($payment_request = $sdk_obj->init_payment( $payment_arr )) )
            {
                if( !$sdk_obj->has_error() )
                    $error_msg = 'Couldn\'t initiate request to server.';
                else
                    $error_msg = 'Call error: '.strip_tags( $sdk_obj->get_error() );

                $s2p_logger->write( $error_msg, 'SDK_payment_error' );

                $response_flow_arr['errors'][] = $error_msg;

                return $response_flow_arr;
            }
        }

        ob_start();
        echo 'PaymentInit RESPONSE';
        var_dump( $payment_request );
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        $response_flow_arr['response'] = $payment_request;

        $redirect_parameters = array();
        $redirect_parameters['_query'] = array();

        $redirect_to_payment = true;
        if( $method_id == $s2p_helper::PAYMENT_METHOD_BT or $method_id == $s2p_helper::PAYMENT_METHOD_SIBS )
            $redirect_to_payment = false;

        $extra_data_arr = array();
        if( !empty( $payment_request['referencedetails'] ) and is_array( $payment_request['referencedetails'] ) )
        {
            // Hack for methods that should return amount to pay
            if( ($method_id == $s2p_helper::PAYMENT_METHOD_BT or $method_id == $s2p_helper::PAYMENT_METHOD_SIBS)
            and empty( $payment_request['referencedetails']['amounttopay'] ) )
            {
                $redirect_to_payment = true;

                $account_currency = false;
                if( !empty( $payment_request['referencedetails']['accountcurrency'] ) )
                    $account_currency = $payment_request['referencedetails']['accountcurrency'];
                elseif( $method_id == $s2p_helper::PAYMENT_METHOD_SIBS )
                    $account_currency = 'EUR';

                if( $account_currency
                and strtolower( $payment_arr['currency'] ) == strtolower( $account_currency ) )
                {
                    $payment_request['referencedetails']['amounttopay'] = number_format( $payment_arr['amount']/100, 2, '.', '' ).' '.$payment_arr['currency'];
                    $redirect_to_payment = false;
                }
            }

            foreach( $payment_request['referencedetails'] as $key => $val )
            {
                if( is_null( $val ) )
                    continue;

                $redirect_parameters['_query'][$key] = $val;
                $extra_data_arr[$key] = $val;
            }
        }

        ob_start();
        echo 'PaymentInit EXTRA';
        var_dump( $extra_data_arr );
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        $payment_status = ((!empty( $payment_request['status'] ) and !empty( $payment_request['status']['id'] ))?$payment_request['status']['id']:0);

        $s2p_transaction = $this->_s2pTransaction->create();

        $s2p_transaction
            ->setMethodID( $method_id )
            ->setMerchantTransactionID( $payment_arr['merchanttransactionid'] )
            ->setSiteID( $api_credentials['site_id'] )
            ->setEnvironment( $config_arr['environment'] )
            ->setExtraDataArray( $extra_data_arr )
            ->set3DSecure( (!empty( $payment_arr['3dsecure'] )?1:0) )
            ->setPaymentStatus( $payment_status )
        ;

        if( !empty( $payment_request['id'] ) )
            $s2p_transaction->setPaymentID( $payment_request['id'] );

        ob_start();
        echo 'PaymentInit SAVE TRANSACTION';
        var_dump( $extra_data_arr );
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        try{
            $s2p_transaction->save();
        } catch( \Exception $e )
        {
            ob_start();
            echo 'PaymentInit ERR TRANSACTION SAVE';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            $s2p_logger->write( 'Failed saving transaction details ['.$payment_arr['merchanttransactionid'].':'.$e->getMessage().']', 'SDK_payment_error', $payment_arr['merchanttransactionid'] );

            $response_flow_arr['errors'][] = __( 'Failed saving transaction for order. Please try again.' );

            return $response_flow_arr;
        }

        if( empty( $payment_request['redirecturl'] ) )
        {
            ob_start();
            echo 'PaymentInit ERR NO REDIRECT';
            $buf = ob_get_clean();

            $s2p_helper->foobar( $buf );

            $s2p_logger->write( 'Redirect URL not provided in API response. Please try again. ['.$payment_arr['merchanttransactionid'].']', 'SDK_payment_error', $payment_arr['merchanttransactionid'] );

            $response_flow_arr['errors'][] = __( 'Redirect URL not provided in API response. Please try again.' );

            return $response_flow_arr;
        }

        $payment->setAdditionalInformation( 'sp_transaction', $s2p_transaction->getID() );
        $payment->setAdditionalInformation( 'sp_payment_id', $s2p_transaction->getPaymentId() );
        $payment->setAdditionalInformation( 'sp_redirect_url', $payment_request['redirecturl'] );
        $payment->setAdditionalInformation( 'sp_do_redirect', $redirect_to_payment );

        ob_start();
        echo 'PaymentInit DONE';
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        // $this->logger->debug(
        //     [
        //         'request' => $request_flow_arr,
        //         'response' => $response_flow_arr
        //     ]
        // );

        return $response_flow_arr;
    }
}
