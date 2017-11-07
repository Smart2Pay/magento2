<?php

namespace Smart2Pay\GlobalPay\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\Data;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use Smart2Pay\GlobalPay\Model\Config\Source\Displaymode;

/**
 * Class Smart2Pay
 */
class Smart2Pay extends AbstractMethod
{
    const METHOD_CODE = 'smart2pay';

    const STATUS_NEW = 'smart2pay_new', STATUS_SUCCESS = 'smart2pay_success', STATUS_CANCELED = 'smart2pay_canceled', STATUS_FAILED = 'smart2pay_failed', STATUS_EXPIRED = 'smart2pay_expired';

    const PAYMENT_METHOD_BT = 1, PAYMENT_METHOD_SIBS = 20;

    const DEFAULT_EMAIL_TEMPLATE_PAYMENT_CONFIRMATION = 'smart2pay_email_payment_confirmation',
          DEFAULT_EMAIL_TEMPLATE_INSTRUCTIONS_SIBS = 'smart2pay_email_payment_instructions_sibs',
          DEFAULT_EMAIL_TEMPLATE_INSTRUCTIONS_BT = 'smart2pay_email_payment_instructions_bt';

    const S2P_STATUS_OPEN = 1, S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PENDING_CUSTOMER = 6,
          S2P_STATUS_PENDING_PROVIDER = 7, S2P_STATUS_SUBMITTED = 8, S2P_STATUS_AUTHORIZED = 9, S2P_STATUS_APPROVED = 10, S2P_STATUS_CAPTURED = 11, S2P_STATUS_REJECTED = 12,
          S2P_STATUS_PENDING_CAPTURE = 13, S2P_STATUS_EXCEPTION = 14, S2P_STATUS_PENDING_CANCEL = 15, S2P_STATUS_REVERSED = 16, S2P_STATUS_COMPLETED = 17, S2P_STATUS_PROCESSING = 18,
          S2P_STATUS_DISPUTED = 19, S2P_STATUS_CHARGEBACK = 20;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canCapture                  = false;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = true;

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'Smart2Pay\GlobalPay\Block\Form\Smart2Pay';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Smart2Pay\GlobalPay\Block\Info\Smart2Pay';

    /** @var \Smart2Pay\GlobalPay\Model\ConfiguredMethods */
    protected $_configuredMethods;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data );

        $this->_s2pTransaction = $transactionFactory;
        $this->_configuredMethods = $configuredMethodsFactory;
    }

    public static function validStatus( $status )
    {

    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @param string $country
     * @return bool
     */
    public function canUseForCountry( $country )
    {
        return true;
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canUseForCurrency( $currencyCode )
    {
        return true;
    }

    public function getFrontConfigArray()
    {
        if( !($full_config_arr = $this->getFullConfigArray()) )
            return array();

        $export_fileds_arr = array( 'display_surcharge', 'display_mode', 'display_description', 'show_methods_in_grid', 'grid_column_number', );

        $return_arr = array();
        foreach( $export_fileds_arr as $key )
        {
            if( !array_key_exists( $key, $full_config_arr ) )
                continue;

            $return_arr[$key] = $full_config_arr[$key];
        }

        return $return_arr;
    }

    public function getFullConfigArray()
    {
        $default_config_array = array(
            'active' => 0,
            'environment' => Environment::ENV_DEMO,
            'post_url_live' => 'https://api.smart2pay.com',
            'post_url_test' => 'https://apitest.smart2pay.com',
            'mid_live' => 0,
            'mid_test' => 0,
            'site_id' => 0,
            'signature_live' => '',
            'signature_test' => '',
            'return_url' => '',
            'title' => 'Smart2Pay - Alternative payment methods',
            'skin_id' => 0,
            'debug_form' => 0,
            'sort_order' => 0,

            'display_surcharge' => 0,
            'display_mode' => Displaymode::MODE_BOTH,
            'display_description' => 0,
            'show_methods_in_grid' => 0,
            'grid_column_number' => 3,
            'product_description_ref' => 1,
            'product_description_custom' => '',

            'notify_customer' => 0,
            'smart2pay_email_payment_confirmation' => self::DEFAULT_EMAIL_TEMPLATE_PAYMENT_CONFIRMATION,
            'notify_payment_instructions' => 0,
            'smart2pay_email_payment_instructions_sibs' => self::DEFAULT_EMAIL_TEMPLATE_INSTRUCTIONS_SIBS,
            'smart2pay_email_payment_instructions_bt' => self::DEFAULT_EMAIL_TEMPLATE_INSTRUCTIONS_BT,

            'auto_invoice' => 0,
            'auto_ship' => 0,
            'order_status' => 'pending',
            'order_status_on_2' => 'processing',
            'order_status_on_3' => 'canceled',
            'order_status_on_4' => 'canceled',
            'order_status_on_5' => 'canceled',

            'skip_payment_page' => 1,
            'redirect_in_iframe' => 0,
            'message_data_2' => __( 'Thank you, the transaction has been processed successfuly. After we receive the final confirmation, we will release the goods.' ),
            'message_data_4' => __( 'There was a problem processing your payment. Please try again.' ),
            'message_data_3' => __( 'You have canceled the payment.' ),
            'message_data_7' => __( 'Thank you, the transaction is pending. After we receive the final confirmation, we will release the goods.' ),
        );

        $extra_config_array = array(
            's2p_code_success' => self::S2P_STATUS_SUCCESS,
            's2p_code_failed' => self::S2P_STATUS_FAILED,
            's2p_code_cancelled' => self::S2P_STATUS_CANCELLED,
            's2p_code_pending' => self::S2P_STATUS_PENDING_PROVIDER,
        );

        $config_arr = array();
        foreach( $default_config_array as $key => $def_value )
        {
            if( ($config_value = $this->getConfigData( $key )) === null )
                $config_value = $def_value;

            $config_arr[$key] = $config_value;
        }

        $config_arr = array_merge( $config_arr, $extra_config_array );

        switch( $config_arr['environment'] )
        {
            default:
            case Environment::ENV_DEMO:
                $config_arr['post_url'] = 'https://apitest.smart2pay.com';
                $config_arr['signature'] = 'c439e420-d4d9';
                $config_arr['mid'] = '1045';
                $config_arr['site_id'] = '30561';
            break;

            case Environment::ENV_TEST:
                $config_arr['post_url'] = $config_arr['post_url_test'];
                $config_arr['signature'] = $config_arr['signature_test'];
                $config_arr['mid'] = $config_arr['mid_test'];
            break;

            case Environment::ENV_LIVE:
                $config_arr['post_url'] = $config_arr['post_url_live'];
                $config_arr['signature'] = $config_arr['signature_live'];
                $config_arr['mid'] = $config_arr['mid_live'];
            break;
        }

        return $config_arr;
    }

    /**
     * Assign corresponding data
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData( $data );

        $infoInstance = $this->getInfoInstance();

        if( ($s2p_method = $data->getSpMethod()) === null )
            $s2p_method = $data->getAdditionalData( 'sp_method' );
        if( ($country_code = $data->getSelectedCountry()) === null )
            $country_code = $data->getAdditionalData( 'selected_country' );

        if( !empty( $country_code ) )
            $country_code = strtoupper( trim( $country_code ) );

        $configured_methods_instance = $this->_configuredMethods->create();

        if( !$s2p_method
         or empty( $country_code )
         or !($method_details = $configured_methods_instance->getConfiguredMethodDetails( $s2p_method, [ 'country_code' => $country_code, 'only_active' => true ] )) )
        {
            // ob_start();
            // var_dump( $data->getSpMethod() );
            // var_dump( $data->getAdditionalData( 'sp_method' ) );
            // var_dump( $s2p_method );
            // var_dump( $country_code );
            // if( isset( $method_details ) )
            //     var_dump( $method_details );
            // $buf = ob_get_clean();

            throw new LocalizedException( __( 'Please select a valid Smart2Pay method first.' ) );
        }

        $details_arr = array();
        if( !empty( $method_details[$country_code] ) )
            $details_arr = $method_details[$country_code];
        elseif( !empty( $method_details[Country::INTERNATIONAL_CODE] ) )
            $details_arr = $method_details[Country::INTERNATIONAL_CODE];

        if( empty( $details_arr ) or !is_array( $details_arr ) )
        {
            throw new LocalizedException( __( 'Couldn\'t obtain Smart2Pay payment method details. Please retry.' ) );
        }

        $s2p_transaction = $this->_s2pTransaction->create();

        $s2p_transaction
            ->setMethodID( $s2p_method )
            ->setMerchantTransactionID( 'NOTSETYET_'.microtime( true ) );

        $s2p_transaction->save();

        $infoInstance->setAdditionalInformation( 'sp_method', $s2p_method );
        $infoInstance->setAdditionalInformation( 'sp_surcharge', (isset( $details_arr['surcharge'] )?$details_arr['surcharge']:0) );
        $infoInstance->setAdditionalInformation( 'sp_fixed_amount', (isset( $details_arr['fixed_amount'] )?$details_arr['fixed_amount']:0) );
        $infoInstance->setAdditionalInformation( 'sp_transaction', $s2p_transaction->getID() );

        return $this;
    }

    /**
     * Order payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canOrder()) {
            throw new LocalizedException(__('The order action is not available.'));
        }

        return $this;
    }

}
