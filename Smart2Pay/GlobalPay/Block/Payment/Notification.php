<?php

namespace Smart2Pay\GlobalPay\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use \Magento\Sales\Model\Order\Invoice;
use \Magento\Framework\App\TemplateTypesInterface;

class Notification extends \Magento\Framework\View\Element\Template
{
    /** @var  \Magento\Framework\Mail\Template\TransportBuilder */
    private $_transportBuilder;

    /** @var \Magento\Sales\Model\OrderFactory */
    protected $_orderFactory;

    /** @var \Magento\Sales\Model\Order\Config */
    protected $_orderConfig;

    /** @var \Magento\Framework\DB\Transaction */
    protected $_dbTransaction;

    /** @var \Magento\Framework\App\Http\Context */
    protected $httpContext;

    /** @var \Smart2Pay\GlobalPay\Model\Logger */
    protected $_s2pLogger;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var \Magento\Sales\Model\Service\InvoiceService */
    protected $_invoiceService;

    /** @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender */
    protected $_invoiceSender;

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper */
    protected $_helper;

    /** @var \Magento\Sales\Model\Order\Payment\Transaction\Repository */
    protected $transactionRepository;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\DB\Transaction $dbTransaction
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Smart2Pay\GlobalPay\Model\Logger $s2pLogger
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\DB\Transaction $dbTransaction,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Smart2Pay\GlobalPay\Model\Logger $s2pLogger,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_orderFactory = $orderFactory;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_transportBuilder = $transportBuilder;
        $this->_dbTransaction = $dbTransaction;

        $this->_invoiceService = $invoiceService;
        $this->_invoiceSender = $invoiceSender;

        $this->_helper = $helperSmart2Pay;

        $this->_s2pTransaction = $s2pTransaction;
        $this->_s2pLogger = $s2pLogger;

        $this->transactionRepository = $transactionRepository;
    }

    protected function _loadCache()
    {
        $helper_obj = $this->_helper;
        $sdk_obj = $helper_obj->getSDKHelper();
        $s2pLogger = $this->_s2pLogger;
        $s2pTransactionLogger = $this->_s2pTransaction->create();
        $order = $this->_orderFactory->create();

        if( !($sdk_version = $sdk_obj::get_sdk_version())
         or !defined( 'S2P_SDK_DIR_CLASSES' )
         or !defined( 'S2P_SDK_DIR_METHODS' ) )
        {
            echo 'Unknown SDK version';
            $s2pLogger->write( 'Unknown SDK version', 'error' );
            exit;
        }

        $api_credentials = $sdk_obj->get_api_credentials();

        $s2pLogger->write( 'SDK version: '.$sdk_version, 'info' );

        if( !defined( 'S2P_SDK_NOTIFICATION_IDENTIFIER' ) )
            define( 'S2P_SDK_NOTIFICATION_IDENTIFIER', microtime( true ) );

        \S2P_SDK\S2P_SDK_Notification::logging_enabled( false );

        $notification_params = array();
        $notification_params['auto_extract_parameters'] = true;

        /** @var \S2P_SDK\S2P_SDK_Notification $notification_obj */
        if( !($notification_obj = \S2P_SDK\S2P_SDK_Module::get_instance( 'S2P_SDK_Notification', $notification_params ))
         or $notification_obj->has_error() )
        {
            if( (\S2P_SDK\S2P_SDK_Module::st_has_error() and $error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
             or (!empty( $notification_obj ) and $notification_obj->has_error() and ($error_arr = $notification_obj->get_error())) )
                $error_msg = 'Error ['.$error_arr['error_no'].']: '.$error_arr['display_error'];
            else
                $error_msg = 'Error initiating notification object.';

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            exit;
        }

        if( !($notification_type = $notification_obj->get_type())
         or !($notification_title = $notification_obj::get_type_title( $notification_type )) )
        {
            $error_msg = 'Unknown notification type.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            exit;
        }

        if( !($result_arr = $notification_obj->get_array()) )
        {
            $error_msg = 'Couldn\'t extract notification object.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            exit;
        }

        if( $notification_type != $notification_obj::TYPE_PAYMENT )
        {
            $error_msg = 'Plugin currently supports only payment notifications.';

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            exit;
        }

        if( empty( $result_arr['payment'] ) or !is_array( $result_arr['payment'] )
         or empty( $result_arr['payment']['merchanttransactionid'] )
         or !($order->loadByIncrementId( $result_arr['payment']['merchanttransactionid'] ))
         or !($s2pTransactionLogger->loadByMerchantTransactionId( $result_arr['payment']['merchanttransactionid'] ))
         or !$s2pTransactionLogger->getID()
          )
        {
            $error_msg = 'Couldn\'t load order or transaction as provided in notification.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write( $error_msg, 'error' );
            echo $error_msg;
            exit;
        }

        $merchanttransactionid = $result_arr['payment']['merchanttransactionid'];
        $payment_arr = $result_arr['payment'];

        $module_config = $helper_obj->getFullConfigArray( false, $order->getStoreId() );

        if( !$s2pTransactionLogger->getEnvironment()
         or !($api_credentials = $helper_obj->getApiSettingsByEnvironment( $s2pTransactionLogger->getEnvironment() ))
         or empty( $api_credentials['site_id'] ) or empty( $api_credentials['apikey'] ) )
        {
            $error_msg = 'Couldn\'t load Smart2Pay API credentials for environment ['.$s2pTransactionLogger->getEnvironment().'].';

            $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
            echo $error_msg;
            exit;
        }

        \S2P_SDK\S2P_SDK_Module::one_call_settings(
            array(
                'api_key' => $api_credentials['apikey'],
                'site_id' => $api_credentials['site_id'],
                'environment' => $api_credentials['api_environment'],
            ) );

        if( !$notification_obj->check_authentication() )
        {
            if( $notification_obj->has_error()
            and ($error_arr = $notification_obj->get_error()) )
                $error_msg = 'Error: '.$error_arr['display_error'];
            else
                $error_msg = 'Authentication failed.';

            $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
            echo $error_msg;
            exit;
        }

        $s2pLogger->write( 'Received notification type ['.$notification_title.'].', 'info', $merchanttransactionid  );

        switch( $notification_type )
        {
            case $notification_obj::TYPE_PAYMENT:

                if( empty( $payment_arr['status'] ) or empty( $payment_arr['status']['id'] ) )
                {
                    $error_msg = 'Status not provided.';
                    $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

                    $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
                    echo $error_msg;
                    exit;
                }

                if( !isset( $payment_arr['amount'] ) or !isset( $payment_arr['currency'] ) )
                {
                    $error_msg = 'Amount or Currency not provided.';
                    $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

                    $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
                    echo $error_msg;
                    exit;
                }

                $order->addStatusHistoryComment( 'S2P Notification: payment notification received (Status: '.$payment_arr['status']['id'].').' );

                if( !($status_title = \S2P_SDK\S2P_SDK_Meth_Payments::valid_status( $payment_arr['status']['id'] )) )
                    $status_title = '(unknown)';

                $something_changed = false;
                if( $s2pTransactionLogger->getPaymentStatus() != $payment_arr['status']['id'] )
                {
                    $something_changed = true;
                    $s2pTransactionLogger->setPaymentStatus( $payment_arr['status']['id'] );
                }
                if( !empty( $payment_arr['methodid'] )
                and $s2pTransactionLogger->getMethodId() != $payment_arr['methodid'] )
                {
                    $something_changed = true;
                    $s2pTransactionLogger->setMethodID( $payment_arr['methodid'] );
                }

                if( !($transaction_extra_data_arr = $s2pTransactionLogger->getExtraDataArray()) )
                    $transaction_extra_data_arr = array();

                if( !empty( $payment_request['referencedetails'] ) and is_array( $payment_request['referencedetails'] ) )
                {
                    foreach( $payment_request['referencedetails'] as $key => $val )
                    {
                        if( is_null( $val )
                         or (array_key_exists( $key, $transaction_extra_data_arr )
                                and (string)$transaction_extra_data_arr[$key] === (string)$val)
                        )
                            continue;

                        $something_changed = true;

                        $transaction_extra_data_arr[$key] = $val;
                    }

                    if( $something_changed )
                        $s2pTransactionLogger->setExtraDataArray( $transaction_extra_data_arr );
                }

                if( $something_changed )
                {
                    try
                    {
                        $s2pTransactionLogger->save();
                    } catch( \Exception $e )
                    {
                        $error_msg = 'Couldn\'t save transaction details to database [#'.$s2pTransactionLogger->getID().', Order: '.$s2pTransactionLogger->getMerchantTransactionId().'].';

                        $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
                        echo $error_msg;
                        exit;
                    }
                }

                // Send order confirmation email (if not already sent)
                // if( !$order->getEmailSent() )
                //     $order->sendNewOrderEmail();

                $s2pLogger->write( 'Received '.$status_title.' notification for order '.$payment_arr['merchanttransactionid'].'.', 'info', $merchanttransactionid );

                // Update database according to payment status
                switch( $payment_arr['status']['id'] )
                {
                    default:
                        $order->addStatusHistoryComment( 'Smart2Pay status ID "'.$payment_arr['status']['id'].'" occurred.' );
                    break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_OPEN:

                        $order->addStatusHistoryComment( 'Smart2Pay status ID "'.$payment_arr['status']['id'].'" occurred.', $module_config['order_status'] );

                        if( false
                        and !empty( $payment_arr['methodid'] )
                        and $module_config['notify_payment_instructions']
                        and in_array( $payment_arr['methodid'], array( $helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS ) ) )
                        {
                            // Inform customer
                            $this->sendPaymentDetails( $order, $transaction_extra_data_arr );
                        }
                    break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_SUCCESS:
                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CAPTURED:
                        $orderAmount =  number_format( $order->getGrandTotal(), 2, '.', '' ) * 100;
                        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();

                        if( strcmp( $orderAmount, $payment_arr['amount'] ) != 0
                         or $orderCurrency != $payment_arr['currency'] )
                        {
                            $order->addStatusHistoryComment( 'S2P Notification: notification has different amount ['.$orderAmount.'/'.$payment_arr['amount'].'] and/or currency ['.$orderCurrency.'/'.$payment_arr['currency'].']!. Please contact support@smart2pay.com', $module_config['order_status_on_4'] );
                            $order->save();
                        } elseif( $order->getState() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW )
                        {
                            $order->addStatusHistoryComment( 'S2P Notification: Order not in payment review state. ['.$order->getState().']' );
                            $order->save();
                        } else
                        {
                            $order->addStatusHistoryComment( 'S2P Notification: order has been paid. [MethodID: '. $payment_arr['methodid'] .']', $module_config['order_status_on_2'] );
                            $order->setState( \Magento\Sales\Model\Order::STATE_PROCESSING );

                            /** @var \Magento\Sales\Model\Order\Payment $payment_obj */
                            if( ($payment_obj = $order->getPayment()) )
                            {
                                if( ($orderTransaction = $this->getOrderTransaction( $payment_obj )) )
                                {
                                    $orderTransaction->setIsClosed( true );
                                    $orderTransaction->save();
                                }

                                $payment_obj->setIsTransactionPending( false );
                                $payment_obj->save();
                            }

                            $order->save();

                            // Generate invoice
                            if( $module_config['auto_invoice'] )
                            {
                                // Create and pay Order Invoice
                                if( !$order->canInvoice() )
                                    $s2pLogger->write( 'Order can not be invoiced', 'warning', $merchanttransactionid );

                                else
                                {
                                    try
                                    {
                                        $invoice = $this->_invoiceService->prepareInvoice( $order );
                                        $invoice->setRequestedCaptureCase( \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE );
                                        $invoice->register();
                                        //$invoice->setState( \Magento\Sales\Model\Order\Invoice::STATE_PAID );
                                        //$invoice->save();

                                        $transactionSave = $this->_dbTransaction->addObject( $invoice )->addObject( $invoice->getOrder() );
                                        $transactionSave->save();

                                        $this->_invoiceSender->send( $invoice );

                                        //send notification code
                                        $order->addStatusHistoryComment(
                                            __( 'S2P Notification: order has been automatically invoiced. #%1.', $invoice->getId() )
                                        );

                                        $order->setIsCustomerNotified( true );
                                        $order->save();

                                    } catch( \Exception $e )
                                    {
                                        $s2pLogger->write( 'Error auto-generating invoice: ['.$e->getMessage().']', 'error', $merchanttransactionid );
                                    }

                                    // /** @var Mage_Sales_Model_Order_Invoice $invoice */
                                    // $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                                    // $invoice->setRequestedCaptureCase( Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE );
                                    // $invoice->register();
                                    // $transactionSave = Mage::getModel('core/resource_transaction')
                                    //     ->addObject( $invoice )
                                    //     ->addObject( $invoice->getOrder() );
                                    // $transactionSave->save();
                                    //
                                    // $order->addStatusHistoryComment( 'S2P Notification: order has been automatically invoiced.' );
                                }
                            }

                            // Check shipment
                            if( $module_config['auto_ship'] )
                            {
                                if( !$order->canShip() )
                                    $s2pLogger->write( 'Order can not be shipped', 'warning', $merchanttransactionid );

                                else
                                {
                                    // $itemQty =  $order->getItemsCollection()->count();
                                    // $shipment = Mage::getModel( 'sales/service_order', $order )->prepareShipment( $itemQty );
                                    // $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                                    // $shipmentId = $shipment->create( $order->getIncrementId() );
                                    // $order->addStatusHistoryComment( 'S2P Notification: order has been automatically shipped.' );
                                }
                            }

                            // Inform customer
                            if( $module_config['notify_customer'] )
                            {
                                $this->informCustomer( $order, $payment_arr['amount'], $payment_arr['currency'] );
                            }
                        }
                    break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CANCELLED:

                        if( !$order->canCancel() )
                            $s2pLogger->write( 'Cannot cancel the order', 'warning', $merchanttransactionid );
                        else
                            $order->cancel();

                        $order->addStatusHistoryComment( 'S2P Notification: payment has been canceled.', $module_config['order_status_on_3'] );
                        $order->setState( \Magento\Sales\Model\Order::STATE_CANCELED );

                        $order->save();

                        // /** @var \Magento\Sales\Model\Order\Payment $payment_obj */
                        // if( ($payment_obj = $order->getPayment()) )
                        // {
                        //     $authTransaction = $payment_obj->getAuthorizationTransaction();
                        //     $authTransaction->setIsClosed( true );
                        //     $authTransaction->save();
                        //
                        //     $payment_obj->setIsTransactionPending( false );
                        //     $payment_obj->save();
                        // }
                    break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_FAILED:

                        if( !$order->canCancel() )
                            $s2pLogger->write( 'Cannot cancel the order', 'warning', $merchanttransactionid );
                        else
                            $order->cancel();

                        $order->addStatusHistoryComment( 'S2P Notification: payment has failed.', $module_config['order_status_on_4'] );
                        $order->setState( \Magento\Sales\Model\Order::STATE_CANCELED );

                        $order->save();

                        // /** @var \Magento\Sales\Model\Order\Payment $payment_obj */
                        // if( ($payment_obj = $order->getPayment()) )
                        // {
                        //     $authTransaction = $payment_obj->getAuthorizationTransaction();
                        //     $authTransaction->setIsClosed( true );
                        //     $authTransaction->save();
                        //
                        //     $payment_obj->setIsTransactionPending( false );
                        //     $payment_obj->save();
                        // }
                    break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_EXPIRED:

                        if( !$order->canCancel() )
                            $s2pLogger->write( 'Cannot cancel the order', 'warning', $merchanttransactionid );
                        else
                            $order->cancel();

                        $order->addStatusHistoryComment( 'S2P Notification: payment has expired.', $module_config['order_status_on_5'] );
                        $order->setState( \Magento\Sales\Model\Order::STATE_CANCELED );

                        $order->save();

                        // /** @var \Magento\Sales\Model\Order\Payment $payment_obj */
                        // if( ($payment_obj = $order->getPayment()) )
                        // {
                        //     $authTransaction = $payment_obj->getAuthorizationTransaction();
                        //     $authTransaction->setIsClosed( true );
                        //     $authTransaction->save();
                        //
                        //     $payment_obj->setIsTransactionPending( false );
                        //     $payment_obj->save();
                        // }
                    break;
                }

            break;

            case $notification_obj::TYPE_PREAPPROVAL:
                $s2pLogger->write( 'Preapprovals not implemented.', 'error', $merchanttransactionid );
            break;
        }

        if( $notification_obj->respond_ok() )
            $s2pLogger->write( '--- Sent OK -------------------------------', 'info', $merchanttransactionid );

        else
        {
            if( $notification_obj->has_error()
                and ($error_arr = $notification_obj->get_error()) )
                $error_msg = 'Error: '.$error_arr['display_error'];
            else
                $error_msg = 'Couldn\'t send ok response.';

            $s2pLogger->write( $error_msg, 'error', $merchanttransactionid );
            echo $error_msg;
        }

        exit;
    }

    /**
     * Get transaction with type order
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return false|\Magento\Sales\Model\Order\Payment\Transaction
     */
    protected function getOrderTransaction( $payment )
    {
        $transaction_obj = false;
        try {
            $transaction_obj = $this->transactionRepository->getByTransactionType(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER,
                $payment->getId(),
                $payment->getOrder()->getId()
            );
        } catch( \Exception $e )
        {
        }

        return $transaction_obj;
    }

    /**
     * Send email with payment details to customer
     *
     * @param Order $order Order
     * @param array $payment_details_arr Payment details
     *
     * @return bool True if success, false if failed
     */
    public function sendPaymentDetails( \Magento\Sales\Model\Order $order, $payment_details_arr )
    {
        $helper_obj = $this->_helper;

        $payment_details_arr = $helper_obj::validate_transaction_reference_values( $payment_details_arr );

        try
        {
            if( !($order_increment_id = $order->getRealOrderId())
             or !($transaction_data = $this->_s2pTransaction->create()->loadByMerchantTransactionId( $order_increment_id )->getData())
             or !($method_config = $helper_obj->getFullConfigArray( false, $order->getStoreId() ))
             or !is_array( $transaction_data ) or empty( $transaction_data['id'] )
             or empty( $transaction_data['method_id'] )
             or !in_array( $transaction_data['method_id'], [$helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS] ) )
                return false;

            $siteUrl = $order->getStore()->getBaseUrl();
            $siteName = $this->_helper->getStoreName( $order->getStoreId() );

            $supportEmail = $this->_helper->getStoreConfig( 'trans_email/ident_support/email', $order->getStoreId() );
            $supportName = $this->_helper->getStoreConfig( 'trans_email/ident_support/name', $order->getStoreId() );

            if( $transaction_data['method_id'] == $helper_obj::PAYMENT_METHOD_SIBS )
            {
                $templateId = $method_config['smart2pay_email_payment_instructions_sibs'];
            } else
            {
                $templateId = $method_config['smart2pay_email_payment_instructions_bt'];
            }

            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtFormatted( \IntlDateFormatter::LONG );
            $payment_details_arr['support_email'] = $supportEmail;

            $this->inlineTranslation->suspend();

            $transport = $this->_transportBuilder->setTemplateIdentifier($templateId)
                                                 ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_ADMINHTML, 'store' => $order->getStoreId()])
                                                 ->setTemplateVars( $payment_details_arr )
                                                 ->setFrom( ['name' => $supportName, 'email' => $supportEmail ] )
                                                 ->addTo( $order->getCustomerEmail() )
                                                 ->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

        } catch( \Magento\Framework\Exception\MailException $e )
        {
            $this->_s2pLogger->write( 'Error sending payment instructions email to ['.$order->getCustomerEmail().']', 'email_template' );
            $this->_s2pLogger->write( $e->getMessage(), 'email_exception' );
        } catch( \Exception $e )
        {
            $this->_s2pLogger->write( $e->getMessage(), 'exception' );
        }

        return true;
    }

    public function informCustomer( \Magento\Sales\Model\Order $order, $amount, $currency)
    {
        $helper_obj = $this->_helper;

        try
        {
            $store_id = $order->getStore()->getId();

            if( !($order_increment_id = $order->getRealOrderId())
             or !($method_config = $helper_obj->getFullConfigArray( false, $store_id )) )
                return false;

            $siteUrl = $order->getStore()->getBaseUrl();
            $siteName = $this->_helper->getStoreName();

            $supportEmail = $this->_helper->getStoreConfig( 'trans_email/ident_support/email', $store_id );
            $supportName = $this->_helper->getStoreConfig( 'trans_email/ident_support/name', $store_id );

            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtFormatted( \IntlDateFormatter::LONG );
            $payment_details_arr['support_email'] = $supportEmail;
            $payment_details_arr['total_paid'] = number_format(($amount / 100), 2);
            $payment_details_arr['currency'] = $currency;

            $this->inlineTranslation->suspend();

            $transport = $this->_transportBuilder->setTemplateIdentifier( $method_config['smart2pay_email_payment_confirmation'] )
                                                 ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_ADMINHTML, 'store' => $store_id ])
                                                 ->setTemplateVars( $payment_details_arr )
                                                 ->setFrom( ['name' => $supportName, 'email' => $supportEmail ] )
                                                 ->addTo( $order->getCustomerEmail() )
                                                 ->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

        } catch( \Magento\Framework\Exception\MailException $e )
        {
            $this->_s2pLogger->write( 'Error sending customer informational email to ['.$order->getCustomerEmail().']', 'email_template' );
            $this->_s2pLogger->write( $e->getMessage(), 'email_exception' );
        } catch ( \Exception $e )
        {
            $this->_s2pLogger->write($e->getMessage(), 'exception');
        }

        return true;
    }

    static function defaultPaymentDetailsParams()
    {
        return array(
            'reference_number' => 0,
            'amount_to_pay' => 0,
            'account_holder' => '',
            'bank_name' => '',
            'account_number' => '',
            'account_currency' => '',
            'swift_bic' => '',
            'iban' => '',
            'entity_number' => '',
            'instructions' => '',
        );
    }

    static function validatePaymentDetailsParams( $query_arr )
    {
        if( empty( $query_arr ) or !is_array( $query_arr ) )
            $query_arr = array();

        $default_values = self::defaultPaymentDetailsParams();
        foreach( $default_values as $key => $val )
        {
            if( !array_key_exists( $key, $query_arr ) )
                $query_arr[$key] = $val;
        }

        return $query_arr;
    }
}
