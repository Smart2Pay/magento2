<?php

namespace Smart2Pay\GlobalPay\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;

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

    /** @var \Magento\Framework\App\Http\Context */
    protected $httpContext;

    /** @var \Smart2Pay\GlobalPay\Model\Logger */
    protected $_s2pLogger;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper */
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
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Smart2Pay\GlobalPay\Model\Logger $s2pLogger,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;

        $this->_helper = $helperSmart2Pay;

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

        $module_settings = $this->_helper->getFullConfigArray();

        $helper_obj = $this->_helper;

        $error_message = '';
        $merchant_transaction_id = 0;

        if( ($status_code = $helper_obj->getParam( 'data', null )) === null )
            $error_message = __( 'Transaction status not provided.' );

        elseif( !($merchant_transaction_id = $helper_obj->getParam( 'MerchantTransactionID', '' ))
             or !($order_id = $helper_obj->convert_from_demo_merchant_transaction_id( $merchant_transaction_id )) )
            $error_message = __( 'Couldn\'t extract transaction information.' );

        elseif( !$s2p_transaction->loadByMerchantTransactionId( $merchant_transaction_id )
             or !$s2p_transaction->getID() )
            $error_message = __( 'Transaction not found in database.' );

        elseif( !$order->loadByIncrementId( $order_id )
             or !$order->getEntityId() )
            $error_message = __( 'Order not found in database.' );

        $status_code = intval( $status_code );

        if( empty( $status_code ) )
            $status_code = $helper_obj::S2P_STATUS_FAILED;

        if( !($all_params = $s2p_transaction->getExtraDataArray()) )
            $all_params = [];

        $transaction_extra_data = [];
        $transaction_details_titles = [];
        // if( in_array( $s2p_transaction->getMethodId(),
        //                     [ $helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS ] ) )
        if( !empty( $all_params ) )
        {
            if( ($transaction_details_titles = $helper_obj::get_transaction_reference_titles())
            and is_array( $transaction_details_titles ) )
            {
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
            if( !($magento_status_id = $helper_obj::convert_gp_status_to_magento_status( $status_code )) )
                $magento_status_id = 0;

            if( isset( $module_settings['message_data_'.$status_code] ) )
                $result_message = $module_settings['message_data_'.$status_code];

            elseif( !empty( $magento_status_id )
            and isset( $module_settings['message_data_'.$magento_status_id] ) )
                $result_message = $module_settings['message_data_'.$magento_status_id];
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
