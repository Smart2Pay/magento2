<?php

namespace Smart2Pay\GlobalPay\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use Smart2Pay\GlobalPay\Model\Smart2Pay;

class Finish extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

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
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Smart2Pay\GlobalPay\Model\Smart2Pay $s2pModel,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Smart2Pay\GlobalPay\Model\Logger $s2pLogger,
        \Smart2Pay\GlobalPay\Helper\Smart2Pay $helperSmart2Pay,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;

        $this->_helper = $helperSmart2Pay;

        $this->_s2pModel = $s2pModel;
        $this->_s2pTransaction = $s2pTransaction;
        $this->_s2pLogger = $s2pLogger;
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $s2p_transaction = $this->_s2pTransaction->create();

        $order = $this->_orderFactory->create();

        $module_settings = $this->_s2pModel->getFullConfigArray();

        $transaction_obj = false;
        $error_message = '';
        $merchant_transaction_id = 0;

        if( ($status_code = $this->_helper->getParam( 'data', null )) === null )
            $error_message = __( 'Transaction status not provided.' );

        elseif( !($merchant_transaction_id = $this->_helper->getParam( 'MerchantTransactionID', '' ))
             or !($merchant_transaction_id = $this->_helper->convert_from_demo_merchant_transaction_id( $merchant_transaction_id )) )
            $error_message = __( 'Couldn\'t extract transaction information.' );

        elseif( !$s2p_transaction->loadByMerchantTransactionId( $merchant_transaction_id )
             or !$s2p_transaction->getID() )
            $error_message = __( 'Transaction not found in database.' );

        elseif( !$order->loadByIncrementId( $merchant_transaction_id )
             or !$order->getEntityId() )
            $error_message = __( 'Order not found in database.' );

        $status_code = intval( $status_code );

        if( empty( $status_code ) )
            $status_code = Smart2Pay::S2P_STATUS_FAILED;

        $transaction_extra_data = [];
        $transaction_details_titles = [];
        if( in_array( $s2p_transaction->getMethodId(),
                            [ \Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_BT, \Smart2Pay\GlobalPay\Model\Smart2Pay::PAYMENT_METHOD_SIBS ] ) )
        {
            if( ($transaction_details_titles = \Smart2Pay\GlobalPay\Helper\Smart2Pay::transaction_logger_params_to_title())
            and is_array( $transaction_details_titles ) )
            {
                if( !($all_params = $this->_helper->getParams()) )
                    $all_params = [];

                foreach( $transaction_details_titles as $key => $title )
                {
                    if( !array_key_exists( $key, $all_params ) )
                        continue;

                    $transaction_extra_data[$key] = $all_params[$key];
                }
            }
        }

        $result_message = __( 'Transaction status is unknown.' );
        if( empty( $error_message ) )
        {
            //map all statuses to known Magento statuses (message_data_2, message_data_4, message_data_3 and message_data_7)
            $status_id_to_string = array(
                Smart2Pay::S2P_STATUS_OPEN => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_SUCCESS => Smart2Pay::S2P_STATUS_SUCCESS,
                Smart2Pay::S2P_STATUS_CANCELLED => Smart2Pay::S2P_STATUS_CANCELLED,
                Smart2Pay::S2P_STATUS_FAILED => Smart2Pay::S2P_STATUS_FAILED,
                Smart2Pay::S2P_STATUS_EXPIRED => Smart2Pay::S2P_STATUS_FAILED,
                Smart2Pay::S2P_STATUS_PENDING_CUSTOMER => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_PENDING_PROVIDER => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_SUBMITTED => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_PROCESSING => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_AUTHORIZED => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_APPROVED => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_CAPTURED => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_REJECTED => Smart2Pay::S2P_STATUS_FAILED,
                Smart2Pay::S2P_STATUS_PENDING_CAPTURE => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_EXCEPTION => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_PENDING_CANCEL => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_REVERSED => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_COMPLETED => Smart2Pay::S2P_STATUS_SUCCESS,
                Smart2Pay::S2P_STATUS_PROCESSING => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_DISPUTED => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
                Smart2Pay::S2P_STATUS_CHARGEBACK => Smart2Pay::S2P_STATUS_PENDING_PROVIDER,
            );

            if( isset( $module_settings['message_data_'.$status_code] ) )
                $result_message = $module_settings['message_data_'.$status_code];

            elseif( !empty( $status_id_to_string[$status_code] )
            and isset( $module_settings['message_data_'.$status_id_to_string[$status_code]] ) )
                $result_message = $module_settings['message_data_'.$status_id_to_string[$status_code]];
        }

        $this->addData(
            [
                'error_message' => $error_message,
                'result_message' => $result_message,

                'transaction_data' => $s2p_transaction->getData(),
                'transaction_extra_data' => $transaction_extra_data,
                'transaction_details_title' => $transaction_details_titles,

                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/order/view/',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_view_order'  => $this->canViewOrder($order),
                'order_id'  => $order->getIncrementId()

            ]
        );
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
               && $this->isVisible($order);
    }
}
