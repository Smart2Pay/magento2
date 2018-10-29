<?php

namespace Smart2Pay\GlobalPay\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use Smart2Pay\GlobalPay\Model\Smart2Pay;

class Send extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /** \Magento\Framework\App\Response\Http $response */
    protected $http_response;

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
     * @param \Magento\Framework\App\Response\Http $response
     * @param \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction
     * @param \Smart2Pay\GlobalPay\Model\Logger $s2pLogger
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\App\Response\Http $response,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Smart2Pay\GlobalPay\Model\Logger $s2pLogger,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->http_response = $response;

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
        $helper_obj = $this->_helper;
        $s2p_transaction = $this->_s2pTransaction->create();

        $order_is_ok = true;
        $order_error_message = '';
        $additional_info = array();
        if( !($order = $this->_checkoutSession->getLastRealOrder()) )
            $order_error_message = __( 'Couldn\'t extract order information.' );

        elseif( !in_array( $order->getState(), array( Order::STATE_NEW, Order::STATE_PENDING_PAYMENT, Order::STATE_PAYMENT_REVIEW ) ) )
            $order_error_message = __( 'Order was already processed or session information expired.' ).print_r( $order->getState(), true );

        elseif( !($additional_info = $order->getPayment()->getAdditionalInformation())
             or !is_array( $additional_info )
             or empty( $additional_info['sp_method'] ) or empty( $additional_info['sp_transaction'] ) )
            $order_error_message = __( 'Couldn\'t extract payment information from order.' );

        elseif( !$s2p_transaction->load( $additional_info['sp_transaction'] ) )
            $order_error_message = __( 'Transaction not found in database.' );

        if( !empty( $order_error_message ) )
            $order_is_ok = false;

        $smart2pay_config = $helper_obj->getFullConfigArray();

        ob_start();
        echo 'IN Send';
        var_dump( $order_is_ok );
        var_dump( $order_error_message );
        $buf = ob_get_clean();

        $helper_obj->foobar( $buf );

        $result_message = '';
        $transaction_extra_data = [];
        $transaction_details_titles = [];
        if( !empty( $order_is_ok ) )
        {
            if( !empty( $additional_info['sp_do_redirect'] )
            and !empty( $additional_info['sp_redirect_url'] ) )
            {
                ob_start();
                echo 'Send';
                echo 'Redirecting to: '.$additional_info['sp_redirect_url'];
                $buf = ob_get_clean();

                $helper_obj->foobar( $buf );

                $order->addCommentToStatusHistory( 'Smart2Pay :: redirecting to payment page for payment ID: '.(!empty( $additional_info['sp_payment_id'] )?$additional_info['sp_payment_id']:'N/A') );

                $this->http_response->setRedirect( $additional_info['sp_redirect_url'] );
            } else
            {
                $status_code = $s2p_transaction->getPaymentStatus();

                if( in_array( $s2p_transaction->getMethodId(),
                              [ $helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS ] ) )
                {
                    if( ($transaction_details_titles = $helper_obj::transaction_logger_params_to_title())
                    and is_array( $transaction_details_titles ) )
                    {
                        if( !($all_params = $s2p_transaction->getExtraDataArray()) )
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
                    // map all statuses to known Magento statuses (message_data_2, message_data_4, message_data_3 and message_data_7)
                    if( !($magento_status_id = $helper_obj::convert_gp_status_to_magento_status( $status_code )) )
                        $magento_status_id = 0;

                    if( isset( $smart2pay_config['message_data_'.$status_code] ) )
                        $result_message = $smart2pay_config['message_data_'.$status_code];

                    elseif( !empty( $magento_status_id )
                        and isset( $smart2pay_config['message_data_'.$magento_status_id] ) )
                        $result_message = $smart2pay_config['message_data_'.$magento_status_id];
                }

                ob_start();
                echo 'Send';
                var_dump( $transaction_extra_data );
                echo 'Result message: ['.$result_message.']';
                $buf = ob_get_clean();

                $helper_obj->foobar( $buf );
            }
        }

        $this->addData(
            [
                'error_message' => $order_error_message,
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
                'order_id'  => $order->getIncrementId(),

                'sp_do_redirect'  => (!empty( $additional_info['sp_do_redirect'] )?true:false),
                'sp_redirect_url'  => (!empty( $additional_info['sp_redirect_url'] )?$additional_info['sp_redirect_url']:''),
            ]
        );
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible( Order $order )
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
