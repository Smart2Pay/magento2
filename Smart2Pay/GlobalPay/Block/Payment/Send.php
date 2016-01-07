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
        $order_is_ok = true;
        $order_error_message = '';
        if( !($order = $this->_checkoutSession->getLastRealOrder()) )
            $order_error_message = __( 'Couldn\'t extract order information.' );

        elseif( $order->getState() != Order::STATE_NEW )
            $order_error_message = __( 'Order was already processed or session information expired.' );

        elseif( !($additional_info = $order->getPayment()->getAdditionalInformation())
             or !is_array( $additional_info )
             or empty( $additional_info['sp_method'] ) or empty( $additional_info['sp_transaction'] ) )
            $order_error_message = __( 'Couldn\'t extract payment information from order.' );

        if( !empty( $order_error_message ) )
            $order_is_ok = false;

        $smart2pay_config = $this->_s2pModel->getFullConfigArray();

        $merchant_transaction_id = $order->getRealOrderId();

        // assume live environment if we don't get something valid from config
        if( empty( $smart2pay_config['environment'] )
         or !($environment = Environment::validEnvironment( $smart2pay_config['environment'] )) )
            $environment = Environment::ENV_LIVE;

        if( $environment == Environment::ENV_DEMO )
            $merchant_transaction_id = $this->_helper->convert_to_demo_merchant_transaction_id( $merchant_transaction_id );

        $form_data = $smart2pay_config;
        $messageToHash = '';

        if( $order_is_ok )
        {
            $form_data['environment'] = $environment;

            $form_data['method_id'] = (!empty( $additional_info['sp_method'] )?intval( $additional_info['sp_method'] ):0);

            $form_data['order_id'] = $merchant_transaction_id;
            $form_data['currency'] = $order->getOrderCurrency()->getCurrencyCode();
            $form_data['amount']   = number_format( $order->getGrandTotal(), 2, '.', '' ) * 100;

            //anonymous user, get the info from billing details
            if( $order->getCustomerId() === null )
            {
                $form_data['customer_last_name']  = $this->_helper->s2p_mb_substr( $order->getBillingAddress()->getLastname(), 0, 30 );
                $form_data['customer_first_name'] = $this->_helper->s2p_mb_substr( $order->getBillingAddress()->getFirstname(), 0, 30 );
                $form_data['customer_name']       = $this->_helper->s2p_mb_substr( $form_data['customer_first_name'] . ' ' . $form_data['customer_last_name'], 0, 30 );
            }
            //else, they're a normal registered user.
            else
            {
                $form_data['customer_name']       = $this->_helper->s2p_mb_substr( $order->getCustomerName(), 0, 30 );
                $form_data['customer_last_name']  = $this->_helper->s2p_mb_substr( $order->getCustomerLastname(), 0, 30 );
                $form_data['customer_first_name'] = $this->_helper->s2p_mb_substr( $order->getCustomerFirstname(), 0, 30 );
            }

            $form_data['customer_email'] = trim( $order->getCustomerEmail() );
            $form_data['country']        = $order->getBillingAddress()->getCountryId();

            $messageToHash = 'MerchantID'.$form_data['mid'].
                             'MerchantTransactionID'.$form_data['order_id'].
                             'Amount'.$form_data['amount'].
                             'Currency'.$form_data['currency'].
                             'ReturnURL'.$form_data['return_url'];

            if( $form_data['site_id'] )
                $messageToHash .= 'SiteID'.$form_data['site_id'];

            $messageToHash .= 'CustomerName'.$form_data['customer_name'];
            $messageToHash .= 'CustomerLastName'.$form_data['customer_last_name'];
            $messageToHash .= 'CustomerFirstName'.$form_data['customer_first_name'];
            $messageToHash .= 'CustomerEmail'.$form_data['customer_email'];
            $messageToHash .= 'Country'.$form_data['country'];
            $messageToHash .= 'MethodID'.$form_data['method_id'];

            $form_data['order_description'] = 'Ref. no.: '.$form_data['order_id'];
            if( empty( $form_data['product_description_ref'] ) )
                $form_data['order_description'] = $form_data['product_description_custom'];

            $messageToHash .= 'Description'.$form_data['order_description'];

            $form_data['skip_hpp'] = 0;
            if( $form_data['skip_payment_page']
            and (!in_array( $form_data['method_id'], [ Smart2Pay::PAYMENT_METHOD_BT, Smart2Pay::PAYMENT_METHOD_SIBS ] )
                    or $form_data['notify_payment_instructions'] ) )
            {
                $form_data['skip_hpp'] = 1;
                $messageToHash .= 'SkipHpp1';
            }

            if( $form_data['redirect_in_iframe'] )
                $messageToHash .= 'RedirectInIframe1';

            if( $form_data['skin_id'] )
                $messageToHash .= 'SkinID'.$form_data['skin_id'];

            $messageToHash .= $form_data['signature'];

            $form_data['message_to_hash'] = $this->_helper->s2p_mb_strtolower( $messageToHash );
            $form_data['hash'] = $this->_helper->computeSHA256Hash( $messageToHash );

            $this->_s2pLogger->write( 'Form hash: ['.$messageToHash.']', 'info' );

            $s2p_transaction = $this->_s2pTransaction->create();

            $s2p_transaction
                ->setID( $additional_info['sp_transaction'] )
                ->setMethodID( $form_data['method_id'] )
                ->setMerchantTransactionID( $form_data['order_id'] )
                ->setSiteID( $form_data['site_id'] )
                ->setEnvironment( $form_data['environment'] );

            $s2p_transaction->save();
        }

        $this->addData(
            [
                'order_ok' => $order_is_ok,
                'error_message' => $order_error_message,
                'order_id'  => $order->getIncrementId(),

                'form_data'  => $form_data,

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
