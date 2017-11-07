<?php

namespace Smart2Pay\GlobalPay\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use Smart2Pay\GlobalPay\Model\Smart2Pay;
use \Magento\Sales\Model\Order\Invoice;

class Notification extends \Magento\Framework\View\Element\Template
{
    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $_transportBuilder;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_dbTransaction;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /** @var \Smart2Pay\GlobalPay\Model\Smart2Pay */
    protected $_s2pModel;

    /** @var \Smart2Pay\GlobalPay\Model\Logger */
    protected $_s2pLogger;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /**
     * Helper
     *
     * @var \Smart2Pay\GlobalPay\Helper\Smart2Pay
     */
    protected $_helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\DB\Transaction $dbTransaction,
        \Smart2Pay\GlobalPay\Model\Smart2Pay $s2pModel,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Smart2Pay\GlobalPay\Model\Logger $s2pLogger,
        \Smart2Pay\GlobalPay\Helper\Smart2Pay $helperSmart2Pay,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_orderFactory = $orderFactory;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_transportBuilder = $transportBuilder;
        $this->_dbTransaction = $dbTransaction;

        $this->_helper = $helperSmart2Pay;

        $this->_s2pModel = $s2pModel;
        $this->_s2pTransaction = $s2pTransaction;
        $this->_s2pLogger = $s2pLogger;
    }

    protected function _loadCache()
    {
        if( !($raw_input = @file_get_contents( 'php://input' )) )
        {
            $this->_s2pLogger->write( 'No input' );
            echo 'No input';
            exit;
        }

        $this->_s2pLogger->write( ' ### Notification START' );

        $method_config = $this->_s2pModel->getFullConfigArray();

        parse_str( $raw_input, $response );

        $recomposedHashString = '';
        if( !empty( $raw_input ) )
        {
            $pairs = explode( '&', $raw_input );
            foreach( $pairs as $pair )
            {
                $nv = explode( "=", $pair, 2 );
                if( !isset( $nv[1] ) )
                    continue;

                if( strtolower( $nv[0] ) != 'hash' )
                    $recomposedHashString .= $nv[0] . $nv[1];
            }
        }

        $recomposedHashString .= $method_config['signature'];

        $this->_s2pLogger->write( 'NotificationRecevied: "' . $raw_input . '"' );

        if( empty( $response['Hash'] ) )
            $response['Hash'] = '';
        if( empty( $response['StatusID'] ) )
            $response['StatusID'] = 0;

        $log_message = '';
        /* @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderFactory->create();

        // Message is intact
        if( $this->_helper->computeSHA256Hash( $recomposedHashString ) != $response['Hash'] )
            $this->_s2pLogger->write( 'Hashes do not match! received: [' . $response['Hash'] . '] recomposed [' . $this->_helper->computeSHA256Hash( $recomposedHashString ) . ']', 'error' );

        elseif( empty( $response['MerchantTransactionID'] ) )
            $this->_s2pLogger->write( 'Unknown merchant transaction ID in request', 'error' );

        elseif( !$order->loadByIncrementId( $response['MerchantTransactionID'] )
             or !$order->getEntityId() )
            $this->_s2pLogger->write( 'Unknown order', 'error' );

        else
        {
            $this->_s2pLogger->write( 'Hashes match' );

            $order->addStatusHistoryComment( 'Smart2Pay notification : "'.$raw_input.'"' );

            /**
             * Check status ID
             */
            switch( $response['StatusID'] )
            {
                case \Smart2Pay\GlobalPay\Model\Smart2Pay::S2P_STATUS_OPEN:

                    if( !empty( $response['MethodID'] )
                    and $method_config['notify_payment_instructions']
                    and in_array( $response['MethodID'], [ \Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_BT, \Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_SIBS ] ) )
                    {
                        $payment_details_arr = self::defaultPaymentDetailsParams();

                        if( isset($response['ReferenceNumber']) )
                            $payment_details_arr['reference_number'] = $response['ReferenceNumber'];
                        if( isset($response['AmountToPay']) )
                            $payment_details_arr['amount_to_pay'] = $response['AmountToPay'];
                        if( isset($response['AccountHolder']) )
                            $payment_details_arr['account_holder'] = $response['AccountHolder'];
                        if( isset($response['BankName']) )
                            $payment_details_arr['bank_name'] = $response['BankName'];
                        if( isset($response['AccountNumber']) )
                            $payment_details_arr['account_number'] = $response['AccountNumber'];
                        if( isset($response['AccountCurrency']) )
                            $payment_details_arr['account_currency'] = $response['AccountCurrency'];
                        if( isset($response['SWIFT_BIC']) )
                            $payment_details_arr['swift_bic'] = $response['SWIFT_BIC'];
                        if( isset($response['IBAN']) )
                            $payment_details_arr['iban'] = $response['IBAN'];
                        if( isset($response['EntityNumber']) )
                            $payment_details_arr['entity_number'] = $response['EntityNumber'];

                        // Inform customer
                        if( $this->sendPaymentDetails( $order, $payment_details_arr ) )
                            $order->addStatusHistoryComment( 'Smart2Pay :: Sending payment details to client.' );
                    }
                break;

                case \Smart2Pay\GlobalPay\Model\Smart2Pay::S2P_STATUS_SUCCESS:
                    // cheking amount  and currency
                    $orderAmount = number_format( $order->getGrandTotal(), 2, '.', '' ) * 100;
                    $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();

                    if( strcmp( $orderAmount, $response['Amount'] ) != 0
                     or $orderCurrency != $response['Currency'] )
                    {
                        $order->addStatusHistoryComment( 'Smart2Pay :: Notification has different amount [' . $orderAmount . '/' . $response['Amount'] . '] and/or currency [' . $orderCurrency . '/' . $response['Currency'] . ']! Please contact support@smart2pay.com', $method_config['order_status_on_4'] );
                        $this->_s2pLogger->write( 'Currency or amount doesn\'t match for order ['.$order->getRealOrderId().'].' );
                    }

                    else
                    {
                        $order->addStatusHistoryComment( 'Smart2Pay :: Order has been paid.', $method_config['order_status_on_2'] );
                        $this->_s2pLogger->write( 'Order paid' );

                        // Generate invoice
                        if( $method_config['auto_invoice'] )
                        {
                            // Create and pay Order Invoice
                            if( !$order->canInvoice() )
                                $this->_s2pLogger->write('Order can not be invoiced', 'warning' );

                            else
                            {
                                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                                $invoice = $order->prepareInvoice();
                                $invoice->setRequestedCaptureCase( Invoice::CAPTURE_OFFLINE );
                                $invoice->register();

                                $this->_dbTransaction
                                        ->addObject( $invoice )
                                        ->addObject( $invoice->getOrder() );

                                $this->_dbTransaction->save();

                                $order->addStatusHistoryComment( 'Smart2Pay :: Order has been automatically invoiced.', $method_config['order_status_on_2'] );
                            }
                        }

                        // Check shipment
                        if( !empty( $method_config['auto_ship'] ) )
                        {
                            if( !$order->canShip() )
                                $this->_s2pLogger->write( 'Order can not be shipped', 'warning' );

                            else
                            {
                                //! TODO: Find how to do auto-shipping

                                //$itemQty =  $order->getItemsCollection()->count();
                                //$shipment = Mage::getModel( 'sales/service_order', $order )->prepareShipment( $itemQty );
                                //$shipment = new Mage_Sales_Model_Order_Shipment_Api();
                                //$shipmentId = $shipment->create( $order->getIncrementId() );
                                //$order->addStatusHistoryComment( 'Smart2Pay :: order has been automatically shipped.', $method_config['order_status_on_2'] );
                            }
                        }

                        // Inform customer
                        if( $method_config['notify_customer'] )
                        {
                            if( $this->informCustomer( $order, $response['Amount'], $response['Currency'] ) )
                                $order->addStatusHistoryComment( 'Smart2Pay :: Customer informed about successful payment.' );
                        }
                    }
                break;

                // Status = canceled
                case \Smart2Pay\GlobalPay\Model\Smart2Pay::S2P_STATUS_CANCELLED:
                    $order->addStatusHistoryComment( 'Smart2Pay :: payment has been canceled.', $method_config['order_status_on_3'] );

                    if( !$order->canCancel() )
                        $this->_s2pLogger->write('Can not cancel the order', 'warning');

                    else
                        $order->cancel();
                break;

                // Status = failed
                case \Smart2Pay\GlobalPay\Model\Smart2Pay::S2P_STATUS_FAILED:
                    $order->addStatusHistoryComment( 'Smart2Pay :: payment has failed.', $method_config['order_status_on_4'] );
                break;

                // Status = expired
                case \Smart2Pay\GlobalPay\Model\Smart2Pay::S2P_STATUS_EXPIRED:
                    $order->addStatusHistoryComment( 'Smart2Pay :: payment has expired.', $method_config['order_status_on_5'] );
                break;

                default:
                    $order->addStatusHistoryComment( 'Smart2Pay status "'.$response['StatusID'].'" occurred.', $method_config['order_status'] );
                break;
            }

            $order->save();

            if( ($s2p_transaction_obj = $this->_s2pTransaction->create()->loadByMerchantTransactionId( $response['MerchantTransactionID'] ))
            and $s2p_transaction_obj->getID() )
            {
                if( isset($response['PaymentID']) )
                    $s2p_transaction_obj->setPaymentID( $response['PaymentID'] );
                if( isset($response['StatusID']) )
                    $s2p_transaction_obj->setPaymentStatus( $response['StatusID'] );

                $s2p_transaction_extra_arr = array();
                $s2p_default_transaction_extra_arr = \Smart2Pay\GlobalPay\Helper\Smart2Pay::defaultTransactionLoggerExtraParams();
                foreach( $s2p_default_transaction_extra_arr as $key => $val )
                {
                    if( array_key_exists( $key, $response ) )
                        $s2p_transaction_extra_arr[$key] = $response[$key];
                }

                if( !empty( $s2p_transaction_extra_arr ) )
                    $s2p_transaction_obj->setExtraDataArray( $s2p_transaction_extra_arr );

                $s2p_transaction_obj->save();
            }

            // NotificationType IS payment
            if( strtolower( $response['NotificationType'] ) == 'payment' )
            {
                // prepare string for 'da hash
                $responseHashString = "notificationTypePaymentPaymentId".$response['PaymentID'].$method_config['signature'];

                // prepare response data
                $responseData = array(
                    'NotificationType' => 'Payment',
                    'PaymentID' => $response['PaymentID'],
                    'Hash' => $this->_helper->computeSHA256Hash( $responseHashString )
                );

                // output response
                echo 'NotificationType=payment&PaymentID='.$responseData['PaymentID'].'&Hash='.$responseData['Hash'];
            }

        }

        if( !empty( $error_message ) )
        {
            $this->_s2pLogger->write( $error_message );
            $this->_s2pLogger->write( ' ### Notification END' );

            echo $error_message;
            exit;
        }

        $this->_s2pLogger->write( ' ### Notification END' );

        exit;
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
        $payment_details_arr = self::validatePaymentDetailsParams( $payment_details_arr );

        try
        {
            if( !($order_increment_id = $order->getRealOrderId())
             or !($method_config = $this->_s2pModel->getFullConfigArray())
             or !($transaction_data = $this->_s2pTransaction->create()->loadByMerchantTransactionId( $order_increment_id )->getData())
             or !is_array( $transaction_data ) or empty( $transaction_data['id'] )
             or empty( $transaction_data['method_id'] )
             or !in_array( $transaction_data['method_id'], [\Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_BT, \Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_SIBS] ) )
                return false;

            $siteUrl = $order->getStore()->getBaseUrl();
            $siteName = $this->_helper->getStoreName();

            $supportEmail = $this->_helper->getStoreConfig( 'trans_email/ident_support/email' );
            $supportName = $this->_helper->getStoreConfig( 'trans_email/ident_support/name' );

            if( $transaction_data['method_id'] == \Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_SIBS )
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

            $transport = $this->_transportBuilder->setTemplateIdentifier($templateId)
                                                 ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_ADMINHTML, 'store' => $order->getStore()->getId()])
                                                 ->setTemplateVars( $payment_details_arr )
                                                 ->setFrom( ['name' => $supportName, 'email' => $supportEmail ] )
                                                 ->addTo( $order->getCustomerEmail() )
                                                 ->getTransport();
            $transport->sendMessage();

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
        try
        {
            if( !($order_increment_id = $order->getRealOrderId())
             or !($method_config = $this->_s2pModel->getFullConfigArray()) )
                return false;

            $siteUrl = $order->getStore()->getBaseUrl();
            $siteName = $this->_helper->getStoreName();

            $supportEmail = $this->_helper->getStoreConfig( 'trans_email/ident_support/email' );
            $supportName = $this->_helper->getStoreConfig( 'trans_email/ident_support/name' );

            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtFormatted( \IntlDateFormatter::LONG );
            $payment_details_arr['support_email'] = $supportEmail;
            $payment_details_arr['total_paid'] = number_format(($amount / 100), 2);
            $payment_details_arr['currency'] = $currency;

            $transport = $this->_transportBuilder->setTemplateIdentifier( $method_config['smart2pay_email_payment_confirmation'] )
                                                 ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_ADMINHTML, 'store' => $order->getStore()->getId()])
                                                 ->setTemplateVars( $payment_details_arr )
                                                 ->setFrom( ['name' => $supportName, 'email' => $supportEmail ] )
                                                 ->addTo( $order->getCustomerEmail() )
                                                 ->getTransport();
            $transport->sendMessage();

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
