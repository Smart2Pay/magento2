<?php

namespace Smart2Pay\GlobalPay\Controller\Payment;

use Magento\Framework\App\Request\Http;

class Notification extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\Mail\Template\TransportBuilder */
    private $transportBuilder;

    /** @var \Magento\Sales\Model\OrderFactory */
    protected $orderFactory;

    /** @var \Magento\Sales\Model\Order\Config */
    protected $orderConfig;

    /** @var \Magento\Framework\DB\Transaction */
    protected $dbTransaction;

    /** @var \Magento\Framework\App\Http\Context */
    protected $httpContext;

    /** @var \Smart2Pay\GlobalPay\Model\Logger */
    protected $s2pLogger;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $s2pTransaction;

    /** @var \Magento\Sales\Model\Service\InvoiceService */
    protected $invoiceService;

    /** @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender */
    protected $invoiceSender;

    /** @var \Magento\Framework\Translate\Inline\StateInterface */
    protected $inlineTranslation;

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper */
    protected $helper;

    /** @var \Magento\Sales\Model\Order\Payment\Transaction\Repository */
    protected $transactionRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\DB\Transaction $dbTransaction,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Smart2Pay\GlobalPay\Model\Logger $s2pLogger,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay
    ) {
        parent::__construct($context);

        $this->orderFactory = $orderFactory;
        $this->orderConfig = $orderConfig;
        $this->httpContext = $httpContext;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->dbTransaction = $dbTransaction;

        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;

        $this->helper = $helperSmart2Pay;
        $this->s2pTransaction = $s2pTransaction;
        $this->s2pLogger = $s2pLogger;

        $this->transactionRepository = $transactionRepository;

        // Ugly bug when sending POST data to a script...
        if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
            $request = $this->getRequest();
            if ($request instanceof Http
            && $request->isPost()) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }

    private function initSDK()
    {
    }


    /**
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $helper_obj = $this->helper;
        $sdk_obj = $helper_obj->getSDKHelper();
        $s2pLogger = $this->s2pLogger;
        $s2pTransactionLogger = $this->s2pTransaction->create();
        $order = $this->orderFactory->create();

        if (!($sdk_version = $sdk_obj::getSDKVersion())
         || !defined('S2P_SDK_DIR_CLASSES')
         || !defined('S2P_SDK_DIR_METHODS')) {
            $error_msg = 'Unknown SDK version';
            $s2pLogger->write($error_msg, 'error');

            return $this->sendResponseError($error_msg, 503);
        }

        $api_credentials = $sdk_obj->getAPICredentials();

        $s2pLogger->write('SDK version: '.$sdk_version, 'info');

        if (!defined('S2P_SDK_NOTIFICATION_IDENTIFIER')) {
            define('S2P_SDK_NOTIFICATION_IDENTIFIER', microtime(true));
        }

        \S2P_SDK\S2P_SDK_Notification::logging_enabled(false);

        $notification_params = [];
        $notification_params['auto_extract_parameters'] = true;

        /** @var \S2P_SDK\S2P_SDK_Notification $notification_obj */
        if (!($notification_obj =
                \S2P_SDK\S2P_SDK_Module::get_instance('S2P_SDK_Notification', $notification_params))
         || $notification_obj->has_error()) {
            if ((\S2P_SDK\S2P_SDK_Module::st_has_error() && $error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
             || (!empty($notification_obj) && $notification_obj->has_error()
                 && ($error_arr = $notification_obj->get_error()))) {
                $error_msg = 'Error ['.$error_arr['error_no'].']: '.$error_arr['display_error'];
            } else {
                $error_msg = 'Error initiating notification object.';
            }

            $s2pLogger->write($error_msg, 'error');

            return $this->sendResponseError($error_msg, 503);
        }

        if (!($notification_type = $notification_obj->get_type())
         || !($notification_title = $notification_obj::get_type_title($notification_type))) {
            $error_msg = 'Unknown notification type.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write($error_msg, 'error');

            return $this->sendResponseError($error_msg, 400);
        }

        if (!($result_arr = $notification_obj->get_array())) {
            $error_msg = 'Couldn\'t extract notification object.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write($error_msg, 'error');
            return $this->sendResponseError($error_msg, 400);
        }

        $notification_type = (int)$notification_type;
        if ($notification_type !== $notification_obj::TYPE_PAYMENT) {
            $error_msg = 'Plugin currently supports only payment notifications.';

            $s2pLogger->write($error_msg, 'error');
            return $this->sendResponseError($error_msg, 406);
        }

        if (empty($result_arr['payment']) || !is_array($result_arr['payment'])
         || empty($result_arr['payment']['merchanttransactionid'])
         || !($order->loadByIncrementId($result_arr['payment']['merchanttransactionid']))
         || !($s2pTransactionLogger->loadByMerchantTransactionId($result_arr['payment']['merchanttransactionid']))
         || !$s2pTransactionLogger->getID()
          ) {
            $error_msg = 'Couldn\'t load order or transaction as provided in notification.';
            $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

            $s2pLogger->write($error_msg, 'error');
            return $this->sendResponseError($error_msg, 404);
        }

        $merchanttransactionid = $result_arr['payment']['merchanttransactionid'];
        $payment_arr = $result_arr['payment'];

        $module_config = $helper_obj->getFullConfigArray(false, $order->getStoreId());

        if (!$s2pTransactionLogger->getEnvironment()
         || !($api_credentials = $helper_obj->getApiSettingsByEnvironment($s2pTransactionLogger->getEnvironment()))
         || empty($api_credentials['site_id']) || empty($api_credentials['apikey'])) {
            $error_msg = 'Couldn\'t load Smart2Pay API credentials for current environment.';

            $s2pLogger->write($error_msg, 'error', $merchanttransactionid);
            return $this->sendResponseError($error_msg, 404);
        }

        \S2P_SDK\S2P_SDK_Module::one_call_settings(
            [
                'api_key' => $api_credentials['apikey'],
                'site_id' => $api_credentials['site_id'],
                'environment' => $api_credentials['api_environment'],
            ]
        );

        if (!$notification_obj->check_authentication()) {
            if ($notification_obj->has_error()
            && ($error_arr = $notification_obj->get_error())) {
                $error_msg = 'Error: '.$error_arr['display_error'];
            } else {
                $error_msg = 'Authentication failed.';
            }

            $s2pLogger->write($error_msg, 'error', $merchanttransactionid);
            return $this->sendResponseError($error_msg, 401);
        }

        $s2pLogger->write('Received notification type ['.$notification_title.'].', 'info', $merchanttransactionid);

        switch ($notification_type) {
            case $notification_obj::TYPE_PAYMENT:
                if (empty($payment_arr['status']) || empty($payment_arr['status']['id'])) {
                    $error_msg = 'Status not provided.';
                    $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

                    $s2pLogger->write($error_msg, 'error', $merchanttransactionid);
                    return $this->sendResponseError($error_msg, 400);
                }

                if (!isset($payment_arr['amount']) || !isset($payment_arr['currency'])) {
                    $error_msg = 'Amount or Currency not provided.';
                    $error_msg .= 'Input buffer: '.$notification_obj->get_input_buffer();

                    $s2pLogger->write($error_msg, 'error', $merchanttransactionid);
                    return $this->sendResponseError($error_msg, 400);
                }

                $order->addStatusHistoryComment('S2P Notification: payment notification received (Status: '.
                                                $payment_arr['status']['id'].').');

                if (!($status_title = \S2P_SDK\S2P_SDK_Meth_Payments::valid_status($payment_arr['status']['id']))) {
                    $status_title = '(unknown)';
                }

                $something_changed = false;
                if ((int)$s2pTransactionLogger->getPaymentStatus() !== (int)$payment_arr['status']['id']) {
                    $something_changed = true;
                    $s2pTransactionLogger->setPaymentStatus($payment_arr['status']['id']);
                }
                if (!empty($payment_arr['methodid'])
                && (int)$s2pTransactionLogger->getMethodId() !== (int)$payment_arr['methodid']) {
                    $something_changed = true;
                    $s2pTransactionLogger->setMethodID($payment_arr['methodid']);
                }

                if (!($transaction_extra_data_arr = $s2pTransactionLogger->getExtraDataArray())) {
                    $transaction_extra_data_arr = [];
                }

                if (!empty($payment_request['referencedetails']) && is_array($payment_request['referencedetails'])) {
                    foreach ($payment_request['referencedetails'] as $key => $val) {
                        if ($val === null
                         || (array_key_exists($key, $transaction_extra_data_arr)
                                && (string)$transaction_extra_data_arr[$key] === (string)$val)
                        ) {
                            continue;
                        }

                        $something_changed = true;
                        $transaction_extra_data_arr[$key] = $val;
                    }

                    if ($something_changed) {
                        $s2pTransactionLogger->setExtraDataArray($transaction_extra_data_arr);
                    }
                }

                if ($something_changed) {
                    try {
                        $s2pTransactionLogger->save();
                    } catch (\Exception $e) {
                        $error_msg = 'Couldn\'t save transaction details to database [#'.
                                     $s2pTransactionLogger->getID().', Order: '.
                                     $s2pTransactionLogger->getMerchantTransactionId().'].';

                        $s2pLogger->write($error_msg, 'error', $merchanttransactionid);
                        return $this->sendResponseError($error_msg, 529);
                    }
                }

                // Send order confirmation email (if not already sent)
                // if( !$order->getEmailSent() )
                //     $order->sendNewOrderEmail();

                $s2pLogger->write('Received '.$status_title.' notification for order '.
                                  $payment_arr['merchanttransactionid'].'.', 'info', $merchanttransactionid);

                // Update database according to payment status
                switch ($payment_arr['status']['id']) {
                    default:
                        $order->addStatusHistoryComment('Smart2Pay status ID "'.
                                                        $payment_arr['status']['id'].'" occurred.');
                        break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_OPEN:
                        $order->addStatusHistoryComment(
                            'Smart2Pay status ID "'.
                                                        $payment_arr['status']['id'].'" occurred.',
                            $module_config['order_status']
                        );

                        // if (!empty($payment_arr['methodid'])
                        // && $module_config['notify_payment_instructions']
                        // && in_array((int)$payment_arr['methodid'], [ $helper_obj::PAYMENT_METHOD_BT,
                        // $helper_obj::PAYMENT_METHOD_SIBS ], true)) {
                        //     // Inform customer
                        //     $this->sendPaymentDetails($order, $transaction_extra_data_arr);
                        // }
                        break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_SUCCESS:
                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CAPTURED:
                        $orderAmount =  number_format(
                            $order->getBaseGrandTotal(),
                            2,
                            '.',
                            ''
                        ) * 100;
                        $orderCurrency = $order->getBaseCurrency()->getCurrencyCode();

                        if ((string)$orderAmount !== (string)$payment_arr['amount']
                         || (string)$orderCurrency !== (string)$payment_arr['currency']) {
                            $order->addStatusHistoryComment('S2P Notification: notification has different amount ['.
                                    $orderAmount.'/'.$payment_arr['amount'].'] and/or currency ['.
                                    $orderCurrency.'/'.$payment_arr['currency'].
                                    ']!. Please contact support@smart2pay.com', $module_config['order_status_on_4']);
                            $order->save();
                        } elseif ($order->getState() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
                            $order->addStatusHistoryComment('S2P Notification: Order not in payment review state. ['.
                                                            $order->getState().']');
                            $order->save();
                        } else {
                            $order->addStatusHistoryComment('S2P Notification: order has been paid. [MethodID: '.
                                                $payment_arr['methodid'] .']', $module_config['order_status_on_2']);
                            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);

                            /** @var \Magento\Sales\Model\Order\Payment $payment_obj */
                            if (($payment_obj = $order->getPayment())) {
                                if (($orderTransaction = $this->getOrderTransaction($payment_obj))) {
                                    $orderTransaction->setIsClosed(true);
                                    $orderTransaction->save();
                                }

                                $payment_obj->setIsTransactionPending(false);
                                $payment_obj->save();
                            }

                            $order->save();

                            // Generate invoice
                            if ($module_config['auto_invoice']) {
                                // Create and pay Order Invoice
                                if (!$order->canInvoice()) {
                                    $s2pLogger->write(
                                        'Order can not be invoiced',
                                        'warning',
                                        $merchanttransactionid
                                    );
                                } else {
                                    try {
                                        $invoice = $this->_invoiceService->prepareInvoice($order);
                                        $invoice->setRequestedCaptureCase(
                                            \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE
                                        );
                                        $invoice->register();
                                        //$invoice->setState( \Magento\Sales\Model\Order\Invoice::STATE_PAID );
                                        //$invoice->save();

                                        $transactionSave = $this->_dbTransaction->addObject($invoice)
                                                                                ->addObject($invoice->getOrder());
                                        $transactionSave->save();

                                        $this->_invoiceSender->send($invoice);

                                        //send notification code
                                        $order->addStatusHistoryComment(
                                            __(
                                                'S2P Notification: order has been automatically invoiced. #%1.',
                                                $invoice->getId()
                                            )
                                        );

                                        //$order->setIsCustomerNotified(true);
                                        $order->save();
                                    } catch (\Exception $e) {
                                        $s2pLogger->write('Error auto-generating invoice: ['.
                                                          $e->getMessage().']', 'error', $merchanttransactionid);
                                    }

                                    // /** @var Mage_Sales_Model_Order_Invoice $invoice */
                                    // $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                                    // $invoice->setRequestedCaptureCase(
                                    // Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE
                                    // );
                                    // $invoice->register();
                                    // $transactionSave = Mage::getModel('core/resource_transaction')
                                    //     ->addObject( $invoice )
                                    //     ->addObject( $invoice->getOrder() );
                                    // $transactionSave->save();
                                    //
                                    // $order->addStatusHistoryComment(
                                    // 'S2P Notification: order has been automatically invoiced.'
                                    // );
                                }
                            }

                            // Check shipment
                            if ($module_config['auto_ship']) {
                                if (!$order->canShip()) {
                                    $s2pLogger->write(
                                        'Order can not be shipped',
                                        'warning',
                                        $merchanttransactionid
                                    );
                                } //else {
                                    // $itemQty =  $order->getItemsCollection()->count();
                                    // $shipment = Mage::getModel( 'sales/service_order', $order )->
                                    // prepareShipment( $itemQty );
                                    // $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                                    // $shipmentId = $shipment->create( $order->getIncrementId() );
                                    // $order->addStatusHistoryComment(
                                    // 'S2P Notification: order has been automatically shipped.'
                                    // );
                                //}
                            }

                            // Inform customer
                            if ($module_config['notify_customer']) {
                                if ($this->informCustomer($order, $payment_arr['amount'], $payment_arr['currency'])) {
                                    $order->setIsCustomerNotified(true);
                                    $order->save();
                                }
                            }
                        }
                        break;

                    case \S2P_SDK\S2P_SDK_Meth_Payments::STATUS_CANCELLED:
                        if (!$order->canCancel()) {
                            $s2pLogger->write(
                                'Cannot cancel the order',
                                'warning',
                                $merchanttransactionid
                            );
                        } else {
                            $order->cancel();
                        }

                        $order->addStatusHistoryComment(
                            'S2P Notification: payment has been canceled.',
                            $module_config['order_status_on_3']
                        );
                        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);

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
                        if (!$order->canCancel()) {
                            $s2pLogger->write(
                                'Cannot cancel the order',
                                'warning',
                                $merchanttransactionid
                            );
                        } else {
                            $order->cancel();
                        }

                        $order->addStatusHistoryComment(
                            'S2P Notification: payment has failed.',
                            $module_config['order_status_on_4']
                        );
                        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);

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
                        if (!$order->canCancel()) {
                            $s2pLogger->write(
                                'Cannot cancel the order',
                                'warning',
                                $merchanttransactionid
                            );
                        } else {
                            $order->cancel();
                        }

                        $order->addStatusHistoryComment(
                            'S2P Notification: payment has expired.',
                            $module_config['order_status_on_5']
                        );
                        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);

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
                $s2pLogger->write('Preapprovals not implemented.', 'error', $merchanttransactionid);
                break;
        }

        if ($notification_obj->respond_ok()) {
            $s2pLogger->write(
                '--- Sent OK -------------------------------',
                'info',
                $merchanttransactionid
            );
        } else {
            if ($notification_obj->has_error()
                && ($error_arr = $notification_obj->get_error())) {
                $error_msg = 'Error: '.$error_arr['display_error'];
            } else {
                $error_msg = 'Couldn\'t send ok response.';
            }

            $s2pLogger->write($error_msg, 'error', $merchanttransactionid);
            return $this->sendResponseError($error_msg, 503);
        }

        return $this->sendResponseOk();
    }

    /**
     * Send OK response
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function sendResponseOk()
    {
        return $this->getResponse()
            ->clearHeader('Content-Type')
            ->setHeader('Content-Type', 'text/plain')
            ->setBody()
            ->setHttpResponseCode(204);
    }

    /**
     * Send Error response with message
     *
     * @param string $message
     * @param int $httpCode
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function sendResponseError($message, $httpCode = 0)
    {
        $this->getResponse()
            ->clearHeader('Content-Type')
            ->setHeader('Content-Type', 'text/plain')
            ->setBody($message);

        if ($httpCode!==0) {
            $this->getResponse()->setHttpResponseCode($httpCode);
        }

        return $this->getResponse();
    }

    /**
     * Get transaction with type order
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return false|\Magento\Sales\Model\Order\Payment\Transaction
     */
    protected function getOrderTransaction($payment)
    {
        $transaction_obj = false;
        try {
            $transaction_obj = $this->transactionRepository->getByTransactionType(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER,
                $payment->getId(),
                $payment->getOrder()->getId()
            );
        } catch (\Exception $e) {
            return false;
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
    public function sendPaymentDetails(\Magento\Sales\Model\Order $order, $payment_details_arr)
    {
        $helper_obj = $this->helper;

        $payment_details_arr = $helper_obj::validateTransactionReferenceValues($payment_details_arr);

        try {
            if (!($order_increment_id = $order->getRealOrderId())
             || !($transaction_data = $this->s2pTransaction->create()->
                            loadByMerchantTransactionId($order_increment_id)->getData())
             || !is_array($transaction_data)
             || empty($transaction_data['id'])
             || empty($transaction_data['method_id'])
             || !in_array(
                 (int)$transaction_data['method_id'],
                 [$helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS],
                 true
             )
             || !($method_config = $helper_obj->getFullConfigArray(false, $order->getStoreId()))
            ) {
                return false;
            }

            $siteUrl = $order->getStore()->getBaseUrl();
            $siteName = $this->helper->getStoreName($order->getStoreId());

            $supportEmail = $this->helper->getStoreConfig(
                'trans_email/ident_support/email',
                $order->getStoreId()
            );
            $supportName = $this->helper->getStoreConfig(
                'trans_email/ident_support/name',
                $order->getStoreId()
            );

            if ((int)$transaction_data['method_id'] === $helper_obj::PAYMENT_METHOD_SIBS) {
                $templateId = $method_config['smart2pay_email_payment_instructions_sibs'];
            } else {
                $templateId = $method_config['smart2pay_email_payment_instructions_bt'];
            }

            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtFormatted(\IntlDateFormatter::LONG);
            $payment_details_arr['support_email'] = $supportEmail;

            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                                                ->setTemplateOptions(
                                                    [
                                                         'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                                                         'store' => $order->getStoreId()
                                                     ]
                                                )
                                                ->setTemplateVars($payment_details_arr)
                                                ->setFrom(['name' => $supportName, 'email' => $supportEmail ])
                                                ->addTo($order->getCustomerEmail())
                                                ->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();
        } catch (\Magento\Framework\Exception\MailException $e) {
            $this->s2pLogger->write(
                'Error sending payment instructions email to ['.$order->getCustomerEmail().']',
                'email_template'
            );
            $this->s2pLogger->write($e->getMessage(), 'email_exception');
        } catch (\Exception $e) {
            $this->s2pLogger->write($e->getMessage(), 'exception');
        }

        return true;
    }

    public function informCustomer(\Magento\Sales\Model\Order $order, $amount, $currency)
    {
        $helper_obj = $this->helper;

        $send_result = true;
        try {
            $store_id = $order->getStore()->getId();

            if (!($order_increment_id = $order->getRealOrderId())
             || !($method_config = $helper_obj->getFullConfigArray(false, $store_id))) {
                return false;
            }

            $siteUrl = $order->getStore()->getBaseUrl();
            $siteName = $this->helper->getStoreName();

            $supportEmail = $this->helper->getStoreConfig('trans_email/ident_support/email', $store_id);
            $supportName = $this->helper->getStoreConfig('trans_email/ident_support/name', $store_id);

            $payment_details_arr['site_url'] = $siteUrl;
            $payment_details_arr['order_increment_id'] = $order_increment_id;
            $payment_details_arr['site_name'] = $siteName;
            $payment_details_arr['customer_name'] = $order->getCustomerName();
            $payment_details_arr['order_date'] = $order->getCreatedAtFormatted(\IntlDateFormatter::LONG);
            $payment_details_arr['support_email'] = $supportEmail;
            $payment_details_arr['total_paid'] = number_format(($amount / 100), 2);
            $payment_details_arr['currency'] = $currency;

            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder->setTemplateIdentifier(
                $method_config['smart2pay_email_payment_confirmation']
            )
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                    'store' => $store_id
                ]
            )
            ->setTemplateVars($payment_details_arr)
            ->setFrom(['name' => $supportName, 'email' => $supportEmail ])
            ->addTo($order->getCustomerEmail())
            ->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();
        } catch (\Magento\Framework\Exception\MailException $e) {
            $this->s2pLogger->write(
                'Error sending customer informational email to ['.$order->getCustomerEmail().']',
                'email_template'
            );
            $this->s2pLogger->write($e->getMessage(), 'email_exception');
            $send_result = false;
        } catch (\Exception $e) {
            $this->s2pLogger->write($e->getMessage(), 'exception');
            $send_result = false;
        }

        return $send_result;
    }

    public static function defaultPaymentDetailsParams()
    {
        return [
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
        ];
    }

    public static function validatePaymentDetailsParams($query_arr)
    {
        if (empty($query_arr) || !is_array($query_arr)) {
            $query_arr = [];
        }

        $default_values = self::defaultPaymentDetailsParams();
        foreach ($default_values as $key => $val) {
            if (!array_key_exists($key, $query_arr)) {
                $query_arr[$key] = $val;
            }
        }

        return $query_arr;
    }
}
