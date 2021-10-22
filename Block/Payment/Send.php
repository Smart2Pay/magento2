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

    /** @var \Magento\Framework\App\Cache\TypeList|\Magento\Framework\App\Cache\TypeList */
    protected $_cacheTypeList;

    /** @var \Magento\Framework\App\Cache\Frontend\Pool  */
    protected $_cacheFrontendPool;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\App\Response\Http $response
     * @param \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction
     * @param \Smart2Pay\GlobalPay\Model\Logger $s2pLogger
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay
     * @param \Magento\Framework\App\Cache\TypeList $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
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
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
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

        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
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

        // Clearing cache affected performance on big sites. In case your transactions don't work
        // please contact us for more investigation on the problem.
        //
        // Sorry for any inconvenience!!! No other way to obtain order after gateway payment processing...
        // If you have a cleaner solution, please contact us!
        // $types = [ 'config', 'layout', 'block_html', 'collections', 'reflection', 'db_ddl', 'eav',
        //                 'config_integration', 'config_integration_api', 'full_page', 'translate',
        //                 'config_webservice' ];
        //
        // foreach ($types as $type) {
        //     $this->_cacheTypeList->cleanType($type);
        // }
        //
        // foreach ($this->_cacheFrontendPool as $cacheFrontend) {
        //     $cacheFrontend->getBackend()->clean();
        // }

        $order_is_ok = true;
        $order_error_message = '';
        $additional_info = [];
        if (!($order = $this->_checkoutSession->getLastRealOrder())) {
            $order_error_message = __('Couldn\'t extract order information.');
        } elseif (!in_array(
            $order->getState(),
            [ Order::STATE_NEW, Order::STATE_PENDING_PAYMENT, Order::STATE_PAYMENT_REVIEW ],
            true
        )) {
            $order_error_message = __('Order was already processed or session information expired.');
        } elseif (!($additional_info = $order->getPayment()->getAdditionalInformation())
             || !is_array($additional_info)
             || empty($additional_info['sp_method']) || empty($additional_info['sp_transaction'])) {
            $order_error_message = __('Couldn\'t extract payment information from order.');
        } elseif (!$s2p_transaction->load($additional_info['sp_transaction'])) {
            $order_error_message = __('Transaction not found in database.');
        }

        if (!empty($order_error_message)) {
            $order_is_ok = false;
        }

        $smart2pay_config = $helper_obj->getFullConfigArray();

        $result_message = '';
        $transaction_extra_data = [];
        $transaction_details_titles = [];
        if (!empty($order_is_ok)) {
            if (!empty($additional_info['sp_do_redirect'])
            && !empty($additional_info['sp_redirect_url'])) {
                $order->addStatusHistoryComment(
                    'Smart2Pay :: redirecting to payment page for payment ID: '.
                    (!empty($additional_info['sp_payment_id'])?$additional_info['sp_payment_id']:'N/A')
                );

                $this->http_response->setRedirect($additional_info['sp_redirect_url']);
            } else {
                $status_code = $s2p_transaction->getPaymentStatus();

                if (!($all_params = $s2p_transaction->getExtraDataArray())) {
                    $all_params = [];
                }

                // if( in_array( $s2p_transaction->getMethodId(),
                //               [ $helper_obj::PAYMENT_METHOD_BT, $helper_obj::PAYMENT_METHOD_SIBS ] ) )
                if (!empty($all_params)) {
                    if (($transaction_details_titles = $helper_obj::getTransactionReferenceTitles())
                    && is_array($transaction_details_titles)) {
                        foreach ($transaction_details_titles as $key => $title) {
                            if (!array_key_exists($key, $all_params)) {
                                continue;
                            }

                            $transaction_extra_data[$key] = $all_params[$key];
                        }
                    }
                }

                $result_message = __('Transaction status is unknown.');
                if (empty($order_error_message)) {
                    // map all statuses to known Magento statuses (message_data_2, message_data_4,
                    // message_data_3 and message_data_7)
                    if (!($magento_status_id = $helper_obj::convertGPStatusToMagentoStatus($status_code))) {
                        $magento_status_id = 0;
                    }

                    if (isset($smart2pay_config['message_data_'.$status_code])) {
                        $result_message = $smart2pay_config['message_data_'.$status_code];
                    } elseif (!empty($magento_status_id)
                        && isset($smart2pay_config['message_data_'.$magento_status_id])) {
                        $result_message = $smart2pay_config['message_data_'.$magento_status_id];
                    }
                }
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

                'sp_do_redirect'  => (!empty($additional_info['sp_do_redirect'])),
                'sp_redirect_url'  => (!empty($additional_info['sp_redirect_url'])?
                    $additional_info['sp_redirect_url']:''),
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
            $this->_orderConfig->getInvisibleOnFrontStatuses(),
            true
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
