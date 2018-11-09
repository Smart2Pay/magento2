<?php

namespace Smart2Pay\GlobalPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\App\ObjectManager;
use \Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use \Smart2Pay\GlobalPay\Model\Config\Source\Displaymode;

class S2pHelper extends AbstractHelper
{
    const METHOD_CODE = 'smart2pay';

    const STATUS_NEW = 'smart2pay_new', STATUS_SUCCESS = 'smart2pay_success', STATUS_CANCELED = 'smart2pay_canceled',
          STATUS_FAILED = 'smart2pay_failed', STATUS_EXPIRED = 'smart2pay_expired';

    const PAYMENT_METHOD_BT = 1, PAYMENT_METHOD_SIBS = 20, PAYMENT_METHOD_SMARTCARDS = 6,
          PAYMENT_METHOD_KLARNA_CHECKOUT = 1052, PAYMENT_METHOD_KLARNA_INVOICE = 75;

    const DEFAULT_EMAIL_TEMPLATE_PAYMENT_CONFIRMATION = 'smart2pay_email_payment_confirmation',
          DEFAULT_EMAIL_TEMPLATE_INSTRUCTIONS_SIBS = 'smart2pay_email_payment_instructions_sibs',
          DEFAULT_EMAIL_TEMPLATE_INSTRUCTIONS_BT = 'smart2pay_email_payment_instructions_bt';

    const S2P_STATUS_OPEN = 1, S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PENDING_CUSTOMER = 6,
          S2P_STATUS_PENDING_PROVIDER = 7, S2P_STATUS_SUBMITTED = 8, S2P_STATUS_AUTHORIZED = 9, S2P_STATUS_APPROVED = 10, S2P_STATUS_CAPTURED = 11,
          S2P_STATUS_REJECTED = 12, S2P_STATUS_PENDING_CAPTURE = 13, S2P_STATUS_EXCEPTION = 14, S2P_STATUS_PENDING_CANCEL = 15, S2P_STATUS_REVERSED = 16,
          S2P_STATUS_COMPLETED = 17, S2P_STATUS_PROCESSING = 18, S2P_STATUS_DISPUTED = 19, S2P_STATUS_CHARGEBACK = 20;

    // After how many hours from last sync action is merchant allowed to sync methods again?
    const RESYNC_AFTER_HOURS = 2;

    const SQL_DATETIME = 'Y-m-d H:i:s', EMPTY_DATETIME = '0000-00-00 00:00:00';
    const SQL_DATE = 'Y-m-d', EMPTY_DATE = '0000-00-00';

    /** @var \Smart2Pay\GlobalPay\Helper\S2pSDK */
    protected $_sdk_helper;

    /** @var \Smart2Pay\GlobalPay\Model\ConfiguredMethods */
    protected $_configuredMethods;

    /** @var \Magento\Directory\Model\CurrencyFactory */
    protected $_currencyFactory;

    /** @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface */
    protected $_resourceConfig;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $timezone;

    /** @var ResolverInterface */
    private $localeResolver;

    /**
     * @param \Smart2Pay\GlobalPay\Helper\S2pSDK $helperS2pSDK
     * @param \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     */
    public function __construct(
        \Smart2Pay\GlobalPay\Helper\S2pSDK $helperS2pSDK,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        ResolverInterface $localeResolver = null
    ) {
        parent::__construct( $context );

        $this->_sdk_helper = $helperS2pSDK;
        $this->_configuredMethods = $configuredMethodsFactory;
        $this->_resourceConfig = $resourceConfig;
        $this->_currencyFactory = $currencyFactory;
        $this->timezone = $timezone;
        $this->localeResolver = $localeResolver ?: ObjectManager::getInstance()->get(ResolverInterface::class);

        $this->_sdk_helper->s2p_helper( $this );
    }

    public static function convert_gp_status_to_magento_status( $status_code )
    {
        $status_id_to_string = array(
            self::S2P_STATUS_OPEN => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_SUCCESS => self::S2P_STATUS_SUCCESS,
            self::S2P_STATUS_CANCELLED => self::S2P_STATUS_CANCELLED,
            self::S2P_STATUS_FAILED => self::S2P_STATUS_FAILED,
            self::S2P_STATUS_EXPIRED => self::S2P_STATUS_FAILED,
            self::S2P_STATUS_PENDING_CUSTOMER => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_PENDING_PROVIDER => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_SUBMITTED => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_AUTHORIZED => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_APPROVED => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_CAPTURED => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_REJECTED => self::S2P_STATUS_FAILED,
            self::S2P_STATUS_PENDING_CAPTURE => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_EXCEPTION => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_PENDING_CANCEL => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_REVERSED => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_COMPLETED => self::S2P_STATUS_SUCCESS,
            self::S2P_STATUS_PROCESSING => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_DISPUTED => self::S2P_STATUS_PENDING_PROVIDER,
            self::S2P_STATUS_CHARGEBACK => self::S2P_STATUS_PENDING_PROVIDER,
        );

        if( !empty( $status_id_to_string[$status_code] ) )
            return $status_id_to_string[$status_code];

        return false;
    }

    public static function default_payment_request_flow()
    {
        return array(
            'errors' => array(),
            's2p_method' => false,
            'payload' => false,
            'payment' => false,
        );
    }

    public static function default_payment_response_flow()
    {
        return array(
            'errors' => array(),
            's2p_method' => false,
            'response' => false,
        );
    }

    public static function validate_payment_request_flow( $flow_arr )
    {
        $default_arr = self::default_payment_request_flow();

        if( empty( $flow_arr ) or !is_array( $flow_arr ) )
            return $default_arr;

        foreach( $default_arr as $key => $def_val )
        {
            if( !array_key_exists( $key, $flow_arr ) )
                $flow_arr[$key] = $def_val;
        }

        return $flow_arr;
    }

    public static function validate_payment_response_flow( $flow_arr )
    {
        $default_arr = self::default_payment_response_flow();

        if( empty( $flow_arr ) or !is_array( $flow_arr ) )
            return $default_arr;

        foreach( $default_arr as $key => $def_val )
        {
            if( !array_key_exists( $key, $flow_arr ) )
                $flow_arr[$key] = $def_val;
        }

        return $flow_arr;
    }

    /**
     * Get formatted date in store timezone
     *
     * @param string|null $date
     * @param string $format date format type (short|medium|long|full)
     * @param string|null $store Store
     * @return string
     */
    public function format_date( $date, $format, $store = null )
    {
        return $this->timezone->formatDateTime(
            new \DateTime( $date ),
            $format,
            $format,
            $this->localeResolver->getDefaultLocale(),
            $this->timezone->getConfigTimezone('store', $store )
        );
    }

    public function has_3d_secure( $method_id )
    {
        $method_id = intval( $method_id );
        return in_array( $method_id, array( self::PAYMENT_METHOD_SMARTCARDS ) );
    }

    public function is_card_method( $method_id )
    {
        $method_id = intval( $method_id );
        return in_array( $method_id, array( self::PAYMENT_METHOD_SMARTCARDS ) );
    }

    public function foobar( $str )
    {
        return true;

        if( !($fil = @fopen( '/home/andy/magento2.log', 'a' )) )
            return false;

        $file = 'N/A';
        $line = 0;
        $backtrace = debug_backtrace();
        if( !empty( $backtrace[0] ) )
        {
            $file = $backtrace[0]['file'];
            $line = $backtrace[0]['line'];
        }

        @fputs( $fil, date( 'Y-m-d H:i:s' ).' - '.$file.':'.$line.' - '.$str."\n" );
        @fflush( $fil );
        @fclose( $fil );

        return true;
    }

    public static function klarna_price( $amount )
    {
        return (float)number_format( $amount, 2, '.', '' );
    }

    public static function cart_products_to_string( $products_arr, $cart_original_amount, $params = false )
    {
        $return_arr = array();
        $return_arr['total_check'] = 0;
        $return_arr['total_to_pay'] = 0;
        $return_arr['total_before_difference_amount'] = 0;
        $return_arr['total_difference_amount'] = 0;
        $return_arr['surcharge_difference_amount'] = 0;
        $return_arr['surcharge_difference_index'] = 0;
        $return_arr['buffer'] = '';
        $return_arr['articles_arr'] = array();
        $return_arr['sdk_articles_arr'] = array();
        $return_arr['articles_meta_arr'] = array();
        $return_arr['transport_index'] = array();

        $cart_original_amount = floatval( $cart_original_amount );

        if( $cart_original_amount == 0
         or empty( $products_arr ) or !is_array( $products_arr ) )
            return $return_arr;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['transport_amount'] ) )
            $params['transport_amount'] = 0;
        if( empty( $params['total_surcharge'] ) )
            $params['total_surcharge'] = 0;
        if( empty( $params['amount_to_pay'] ) )
            $params['amount_to_pay'] = $cart_original_amount;

        $amount_to_pay = floatval( $params['amount_to_pay'] );

        $return_arr['total_to_pay'] = $amount_to_pay;

        $return_str = '';
        $articles_arr = array();
        $sdk_articles_arr = array();
        $articles_meta_arr = array();
        $articles_knti = 0;
        $items_total_amount = 0;
        $biggest_price = 0;
        $biggest_price_knti = 0;
        foreach( $products_arr as $product_arr )
        {
            if( empty( $product_arr ) or !is_array( $product_arr ) )
                continue;

            // If products are from quotes we should use qty
            if( !isset( $product_arr['qty_ordered'] )
            and isset( $product_arr['qty'] ) )
                $product_arr['qty_ordered'] = $product_arr['qty'];

            // 1 => 'Product', 2 => 'Shipping', 3 => 'Handling',
            $article_arr = array();
            $article_arr['ID'] = $product_arr['product_id'];
            $article_arr['Name'] = $product_arr['name'];
            $article_arr['Quantity'] = intval( $product_arr['qty_ordered'] );
            $article_arr['Price'] = self::klarna_price( $product_arr['price_incl_tax'] );
            // VAT Percent
            $article_arr['VAT'] = self::klarna_price( $product_arr['tax_percent'] );
            // $article_arr['Discount'] = 0;
            $article_arr['Type'] = 1;

            if( $article_arr['Price'] > $biggest_price )
                $biggest_price_knti = $articles_knti;

            $articles_arr[$articles_knti] = $article_arr;

            $article_meta_arr = array();
            $article_meta_arr['total_price'] = (float)($article_arr['Price'] * $article_arr['Quantity']);
            $article_meta_arr['price_perc'] = ($article_meta_arr['total_price'] * 100) / $cart_original_amount;
            $article_meta_arr['surcharge_amount'] = 0;

            $articles_meta_arr[$articles_knti] = $article_meta_arr;

            $items_total_amount += $article_meta_arr['total_price'];

            $articles_knti++;
        }

        if( empty( $articles_arr ) )
            return $return_arr;

        $transport_index = 0;
        if( $params['transport_amount'] != 0 )
        {
            // 1 => 'Product', 2 => 'Shipping', 3 => 'Handling',
            $article_arr = array();
            $article_arr['ID'] = 0;
            $article_arr['Name'] = 'Transport';
            $article_arr['Quantity'] = 1;
            $article_arr['Price'] = self::klarna_price( $params['transport_amount'] );
            $article_arr['VAT'] = 0;
            //$article_arr['Discount'] = 0;
            $article_arr['Type'] = 2;

            $articles_arr[$articles_knti] = $article_arr;

            $transport_index = $articles_knti;

            $article_meta_arr = array();
            $article_meta_arr['total_price'] = (float)($article_arr['Price'] * $article_arr['Quantity']);
            $article_meta_arr['price_perc'] = 0;
            $article_meta_arr['surcharge_amount'] = 0;

            $articles_meta_arr[$articles_knti] = $article_meta_arr;

            $items_total_amount += $article_meta_arr['total_price'];

            $articles_knti++;
        }

        // Apply surcharge (if required) depending on product price percentage of full amount
        $total_surcharge = 0;
        if( $params['total_surcharge'] != 0 )
        {
            $total_surcharge = $params['total_surcharge'];
            foreach( $articles_arr as $knti => $article_arr )
            {
                if( $articles_arr[$knti]['Type'] != 1 )
                    continue;

                $total_article_surcharge = (($articles_meta_arr[$knti]['price_perc'] * $params['total_surcharge'])/100);

                $article_unit_surcharge = self::klarna_price( $total_article_surcharge/$articles_arr[$knti]['Quantity'] );

                $articles_arr[$knti]['Price'] += $article_unit_surcharge;
                $articles_meta_arr[$knti]['surcharge_amount'] = $article_unit_surcharge;

                $items_total_amount += ($article_unit_surcharge * $articles_arr[$knti]['Quantity']);
                $total_surcharge -= ($article_unit_surcharge * $articles_arr[$knti]['Quantity']);
            }

            // If after applying all surcharge amounts as percentage of each product price we still have a difference, apply difference on product with biggest price
            if( $total_surcharge != 0 )
            {
                $article_unit_surcharge = self::klarna_price( $total_surcharge/$articles_arr[$biggest_price_knti]['Quantity'] );

                $articles_arr[$biggest_price_knti]['Price'] += $article_unit_surcharge;
                $articles_meta_arr[$biggest_price_knti]['surcharge_amount'] += $article_unit_surcharge;
                $items_total_amount += ($article_unit_surcharge * $articles_arr[$biggest_price_knti]['Quantity']);

                $return_arr['surcharge_difference_amount'] = $total_surcharge;
                $return_arr['surcharge_difference_index'] = $biggest_price_knti;
            }
        }

        $return_arr['total_before_difference_amount'] = $items_total_amount;

        if( self::klarna_price( $items_total_amount ) != self::klarna_price( $amount_to_pay ) )
        {
            // v1. If we still have a difference apply it on biggest price product
            //$amount_diff = self::klarna_price( ($amount_to_pay - $items_total_amount)/$articles_arr[$biggest_price_knti]['Quantity'] );
            //$articles_arr[$biggest_price_knti]['Price'] += $amount_diff;

            // v2. If we still have a difference apply it on transport as it has quantity of 1 and we can apply a difference of 1 cent
            $amount_diff = self::klarna_price( $amount_to_pay - $items_total_amount );
            if( $transport_index )
            {
                // we have transport in articles...
                $articles_arr[$transport_index]['Price'] += $amount_diff;
                $articles_meta_arr[$transport_index]['total_price'] += $amount_diff;
            } else
            {
                // we DON'T have transport in articles...
                // 1 => 'Product', 2 => 'Shipping', 3 => 'Handling',
                $article_arr = array();
                $article_arr['ID'] = 0;
                $article_arr['Name'] = 'Transport';
                $article_arr['Quantity'] = 1;
                $article_arr['Price'] = $amount_diff;
                $article_arr['VAT'] = 0;
                //$article_arr['Discount'] = 0;
                $article_arr['Type'] = 2;

                $articles_arr[$articles_knti] = $article_arr;

                $transport_index = $articles_knti;

                $article_meta_arr = array();
                $article_meta_arr['total_price'] = $article_arr['Price'];
                $article_meta_arr['price_perc'] = 0;
                $article_meta_arr['surcharge_amount'] = 0;

                $articles_meta_arr[$articles_knti] = $article_meta_arr;

                $articles_knti++;
            }

            $return_arr['total_difference_amount'] = self::klarna_price( $amount_to_pay - $items_total_amount );
        }

        $return_arr['transport_index'] = $transport_index;

        $total_check = 0;
        $hpp_to_sdk_keys = array(
            'ID' => 'merchantarticleid',
            'Name' => 'name',
            'Quantity' => 'quantity',
            'Price' => 'price',
            'VAT' => 'vat',
            'Discount' => 'discount',
            'Type' => 'type',
        );
        foreach( $articles_arr as $knti => $article_arr )
        {
            $total_check += (float)($article_arr['Price'] * $article_arr['Quantity']);

            $article_arr['Price'] = $article_arr['Price'] * 100;
            $article_arr['VAT'] = $article_arr['VAT'] * 100;
            //$article_arr['Discount'] = $article_arr['Discount'] * 100;

            $article_buf = '';
            $sdk_article = array();
            foreach( $article_arr as $key => $val )
            {
                if( !empty( $hpp_to_sdk_keys[$key] ) )
                    $sdk_article[$hpp_to_sdk_keys[$key]] = $val;

                $article_buf .= ($article_buf!=''?'&':'').$key.'='.str_replace( array( '&', ';', '=' ), ' ', $val );
            }

            if( !empty( $sdk_article ) )
                $sdk_articles_arr[] = $sdk_article;

            $return_arr['buffer'] .= $article_buf.';';
        }

        $return_arr['buffer'] = substr( $return_arr['buffer'], 0, -1 );

        // $return_arr['buffer'] = rawurlencode( $return_arr['buffer'] );

        $return_arr['total_check'] = $total_check;
        $return_arr['articles_arr'] = $articles_arr;
        $return_arr['articles_meta_arr'] = $articles_meta_arr;

        $return_arr['sdk_articles_arr'] = $sdk_articles_arr;

        return $return_arr;
    }

    /**
     * @return \Smart2Pay\GlobalPay\Helper\S2pSDK
     */
    public function getSDKHelper()
    {
        return $this->_sdk_helper;
    }

    public function getBaseCurrencies()
    {
        $currency = $this->_currencyFactory->create();
        return $currency->getConfigBaseCurrencies();
    }

    public function getStoreName( $storeId = null )
    {
        return $this->scopeConfig->getValue( 'general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
    }

    public function getStoreConfig( $path, $storeId = null )
    {
        return $this->scopeConfig->getValue( $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
    }

    public function getModuleConfig( $field, $storeId = null )
    {
        $path = 'payment/' . self::METHOD_CODE . '/' . $field;

        return $this->scopeConfig->getValue( $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
    }

    public function getFrontConfigArray( $storeId = null )
    {
        if( !($full_config_arr = $this->getFullConfigArray( false, $storeId )) )
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

    public function getFullConfigArray( $force = false, $storeId = null )
    {
        static $config_arr = false;

        if( empty( $force )
        and !empty( $config_arr )
        and is_array( $config_arr ) )
            return $config_arr;

        $default_config_array = array(
            'active' => 0,
            'last_sync_demo' => false,
            'last_sync_test' => false,
            'last_sync_live' => false,
            'site_id_test' => 0,
            'apikey_test' => '',
            'site_id_live' => 0,
            'apikey_live' => '',
            'return_url' => '',
            'title' => 'Smart2Pay - Alternative payment methods',
            'skin_id' => 0,
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
            'use_3dsecure' => 0,
            'order_status' => 'pending',
            'order_status_on_2' => 'processing',
            'order_status_on_3' => 'canceled',
            'order_status_on_4' => 'canceled',
            'order_status_on_5' => 'canceled',

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

        $config_arr['environment'] = $this->getEnvironment( $storeId );

        foreach( $default_config_array as $key => $def_value )
        {
            if( ($config_value = $this->getModuleConfig( $key, $storeId )) === null )
                $config_value = $def_value;

            $config_arr[$key] = $config_value;
        }

        $config_arr = array_merge( $config_arr, $extra_config_array );

        if( ($api_settings = $this->getApiSettingsByEnvironment( $config_arr['environment'], $storeId )) )
        {
            foreach( $api_settings as $api_arr_key => $api_arr_val )
            {
                $config_arr[$api_arr_key] = $api_arr_val;
            }
        }

        return $config_arr;
    }

    public function getApiSettingsByEnvironment( $environment = false, $storeId = null )
    {
        if( empty( $environment ) )
            $environment = $this->getEnvironment( $storeId );

        $api_settings = array();
        if( $environment == Environment::ENV_DEMO )
        {
            // demo environment
            $api_settings['api_environment'] = Environment::ENV_TEST;
            $api_settings['apikey'] = 'gb0LO1CS7iHihW+3yygoPJOmrlrQ2e0UQRGKPdznYgFW3mohdg';
            $api_settings['site_id'] = '33844';
            $api_settings['last_sync'] = $this->getModuleConfig( 'last_sync_demo', $storeId );
        } elseif( in_array( $environment, array( Environment::ENV_TEST, Environment::ENV_LIVE ) ) )
        {
            $api_settings['api_environment'] = $environment;
            $api_settings['apikey'] = $this->getModuleConfig( 'apikey_' . $environment, $storeId );
            $api_settings['site_id'] = $this->getModuleConfig( 'site_id_' . $environment, $storeId );
            $api_settings['last_sync'] = $this->getModuleConfig( 'last_sync_' . $environment, $storeId );
        } else
        {
            $api_settings['api_environment'] = '';
            $api_settings['apikey'] = '';
            $api_settings['site_id'] = 0;
            $api_settings['last_sync'] = false;
        }

        return $api_settings;
    }

    public function getEnvironment( $storeId = null )
    {
        static $_environment = false;

        if( $storeId === null
        and $_environment !== false )
            return $_environment;

        if( !($config_environment = $this->getModuleConfig( 'environment', $storeId )) )
            $config_environment = Environment::ENV_DEMO;

        $config_environment = strtolower( trim( $config_environment ) );
        if( !Environment::validEnvironment( $config_environment ) )
            $config_environment = Environment::ENV_DEMO;

        if( $storeId === null )
            $_environment = $config_environment;

        return $config_environment;
    }

    public function get_api_credentials( $storeId = null )
    {
        $api_settings = $this->getApiSettingsByEnvironment( $storeId );
        $method_settings = $this->getFullConfigArray( $storeId );

        if( !in_array( $api_settings['api_environment'], array( 'live', 'test' ) ) )
            $api_settings['api_environment'] = 'test';

        $return_arr = array();
        $return_arr['api_key'] = (empty( $api_settings['apikey'] )?'':$api_settings['apikey']);
        $return_arr['site_id'] = (empty( $api_settings['site_id'] )?0:$api_settings['site_id']);
        $return_arr['skin_id'] = (empty( $method_settings['skin_id'] )?0:$method_settings['skin_id']);
        $return_arr['environment'] = $api_settings['api_environment'];

        return $return_arr;
    }

    public function upate_last_methods_sync_option( $value, $environment = false )
    {
        if( $environment === false )
            $environment = $this->getEnvironment();

        $this->_resourceConfig->saveConfig(
            'payment/smart2pay/last_sync_'.$environment,
            $value,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
    }

    public function last_methods_sync_option( $value = null )
    {
        if( $value === null )
        {
            if( !($full_config_arr = $this->getFullConfigArray())
                or empty( $full_config_arr['last_sync'] ) )
                return false;

            return $full_config_arr['last_sync'];
        }

        if( empty( $value ) )
            $value = date( self::SQL_DATETIME );

        $this->upate_last_methods_sync_option( $value );

        return $value;
    }

    public function seconds_to_launch_sync()
    {
        $resync_seconds = self::RESYNC_AFTER_HOURS * 1200;
        $time_diff = 0;
        if( !($last_sync_date = $this->last_methods_sync_option())
         or ($time_diff = abs( self::seconds_passed( $last_sync_date ) )) > $resync_seconds )
            return 0;

        return $resync_seconds - $time_diff;
    }

    /**
     * @return bool|string
     */
    public function seconds_to_launch_sync_str()
    {
        if( !($seconds_to_sync = $this->seconds_to_launch_sync()) )
            return false;

        $hours_to_sync = floor( $seconds_to_sync / 1200 );
        $minutes_to_sync = floor( ($seconds_to_sync - ($hours_to_sync * 1200)) / 60 );
        $seconds_to_sync -= ($hours_to_sync * 1200) + ($minutes_to_sync * 60);

        $sync_interval = '';
        if( $hours_to_sync )
            $sync_interval = $hours_to_sync.' hour(s)';

        if( $hours_to_sync or $minutes_to_sync )
            $sync_interval .= ($sync_interval!=''?', ':'').$minutes_to_sync.' minute(s)';

        $sync_interval .= ($sync_interval!=''?', ':'').$seconds_to_sync.' seconds';

        return $sync_interval;
    }

    public function s2p_mb_substr( $message, $start, $length )
    {
        if( @function_exists( 'mb_substr' ) )
            return mb_substr( $message, $start, $length, 'UTF-8' );
        else
            return substr( $message, $start, $length );
    }

    public function s2p_mb_strtolower( $message )
    {
        if( @function_exists( 'mb_strtolower' ) )
            return mb_strtolower( $message, 'UTF-8' );
        else
            return strtolower( $message );
    }

    public function convert_to_demo_merchant_transaction_id( $mt_id )
    {
        return 'DEMO_'.base_convert( time(), 10, 36 ).'_'.$mt_id;
    }

    public function convert_from_demo_merchant_transaction_id( $mt_id )
    {
        if( strstr( $mt_id, '_' ) !== false
        and strtoupper( substr( $mt_id, 0, 4 ) ) == 'DEMO'
        and ($mtid_arr = explode( '_', $mt_id, 3 ))
        and !empty( $mtid_arr[2] ) )
            $mt_id = $mtid_arr[2];

        return $mt_id;
    }

    public static function default_transaction_reference_details()
    {
        static $return_arr = [];

        if( !empty( $return_arr ) )
            return $return_arr;

        $return_arr = [
            'accountholder' => array(
                'title' => __( 'Account Holder' )->render(),
                'default' => '',
            ),
            'bankname' => array(
                'title' => __( 'Bank Name' )->render(),
                'default' => '',
            ),
            'accountnumber' => array(
                'title' => __( 'Account Number' )->render(),
                'default' => '',
            ),
            'iban' => array(
                'title' => __( 'IBAN' )->render(),
                'default' => '',
            ),
            'swift_bic' => array(
                'title' => __( 'SWIFT / BIC' )->render(),
                'default' => '',
            ),
            'accountcurrency' => array(
                'title' => __( 'Account Currency' )->render(),
                'default' => '',
            ),

            'entitynumber' => array(
                'title' => __( 'Entity Number' )->render(),
                'default' => '',
            ),

            'referencenumber' => array(
                'title' => __( 'Reference Number' )->render(),
                'default' => '',
            ),
            'amounttopay' => array(
                'title' => __( 'Amount To Pay' )->render(),
                'default' => '',
            ),

            'instructions' => array(
                'title' => __( 'Payment Instructions' )->render(),
                'default' => '',
            ),
        ];

        return $return_arr;
    }

    public static function get_transaction_reference_titles()
    {
        static $return_arr = [];

        if( !empty( $return_arr ) )
            return $return_arr;

        $defaults = self::default_transaction_reference_details();

        foreach( $defaults as $key => $details_arr )
        {
            if( !isset( $details_arr['title'] ) )
                continue;

            $return_arr[$key] = $details_arr['title'];
        }

        return $return_arr;
    }

    public static function validate_transaction_reference_values( $params_arr )
    {
        if( empty( $params_arr ) or !is_array( $params_arr ) )
            return array();

        $default_values = self::default_transaction_reference_details();
        foreach( $default_values as $key => $details_arr )
        {
            if( !isset( $details_arr['default'] )
             or !array_key_exists( $key, $params_arr ) )
                continue;

            $val = $details_arr['default'];

            if( is_int( $val ) )
                $new_val = (int)$params_arr[$key];
            elseif( is_float( $val ) )
                $new_val = (float)$params_arr[$key];
            elseif( is_string( $val ) )
                $new_val = trim( $params_arr[$key] );
            else
                $new_val = $params_arr[$key];

            if( (string)$new_val === (string)$val )
                continue;

            $params_arr[$key] = $new_val;
        }

        return $params_arr;
    }

    /**
     * Retrieve param by key
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getParam( $key, $defaultValue = null )
    {
        return $this->_request->getParam( $key, $defaultValue );
    }

    /**
     * Retrieve all request params
     * @return array()
     */
    public function getParams()
    {
        return $this->_request->getParams();
    }

    static public function value_to_string( $val )
    {
        if( is_object( $val ) or is_resource( $val ) )
            return false;

        if( is_array( $val ) )
            return @json_encode( $val );

        if( is_string( $val ) )
            return '\''.$val.'\'';

        if( is_bool( $val ) )
            return (!empty( $val )?'true':'false');

        if( is_null( $val ) )
            return 'null';

        if( is_numeric( $val ) )
            return $val;

        return false;
    }

    static public function string_to_value( $str )
    {
        if( !is_string( $str ) )
            return null;

        if( ($val = @json_decode( $str, true )) !== null )
            return $val;

        if( is_numeric( $str ) )
            return $str;

        if( ($tch = substr( $str, 0, 1 )) == '\'' or $tch = '"' )
            $str = substr( $str, 1 );
        if( ($tch = substr( $str, -1 )) == '\'' or $tch = '"' )
            $str = substr( $str, 0, -1 );

        $str_lower = strtolower( $str );
        if( $str_lower == 'null' )
            return null;

        if( $str_lower == 'false' )
            return false;

        if( $str_lower == 'true' )
            return true;

        return $str;
    }

    static public function to_string( $lines_data )
    {
        if( empty( $lines_data ) or !is_array( $lines_data ) )
            return '';

        $lines_str = '';
        $first_line = true;
        foreach( $lines_data as $key => $val )
        {
            if( !$first_line )
                $lines_str .= "\r\n";

            $first_line = false;

            // In normal cases there cannot be '=' char in key so we interpret that value should just be passed as-it-is
            if( substr( $key, 0, 1 ) == '=' )
            {
                $lines_str .= $val;
                continue;
            }

            // Don't save if error converting to string
            if( ($line_val = self::value_to_string( $val )) === false )
                continue;

            $lines_str .= $key.'='.$line_val;
        }

        return $lines_str;
    }

    static public function parse_string_line( $line_str, $comment_no = 0 )
    {
        if( !is_string( $line_str ) )
            $line_str = '';

        // allow empty lines (keeps file 'styling' same)
        if( trim( $line_str ) == '' )
            $line_str = '';

        $return_arr = array();
        $return_arr['key'] = '';
        $return_arr['val'] = '';
        $return_arr['comment_no'] = $comment_no;

        $first_char = substr( $line_str, 0, 1 );
        if( $line_str == '' or $first_char == '#' or $first_char == ';' )
        {
            $comment_no++;

            $return_arr['key'] = '='.$comment_no.'='; // comment count added to avoid comment key overwrite
            $return_arr['val'] = $line_str;
            $return_arr['comment_no'] = $comment_no;

            return $return_arr;
        }

        $line_details = explode( '=', $line_str, 2 );
        $key = trim( $line_details[0] );

        if( $key == '' )
            return false;

        if( !isset( $line_details[1] ) )
        {
            $return_arr['key'] = $key;
            $return_arr['val'] = '';

            return $return_arr;
        }

        $return_arr['key'] = $key;
        $return_arr['val'] = self::string_to_value( $line_details[1] );

        return $return_arr;
    }

    /**
     * @param string $string String to be parsed
     *
     * @return array Returns an array of key-values parsed from provided string
     */
    static public function parse_string( $string )
    {
        if( empty( $string )
            or (!is_array( $string ) and !is_string( $string )) )
            return array();

        if( is_array( $string ) )
            return $string;

        $string = str_replace( "\r", "\n", str_replace( array( "\r\n", "\n\r" ), "\n", $string ) );
        $lines_arr = explode( "\n", $string );

        $return_arr = array();
        $comment_no = 1;
        foreach( $lines_arr as $line_nr => $line_str )
        {
            if( !($line_data = self::parse_string_line( $line_str, $comment_no ))
                or !is_array( $line_data ) or !isset( $line_data['key'] ) or $line_data['key'] == '' )
                continue;

            $return_arr[$line_data['key']] = $line_data['val'];
            $comment_no = $line_data['comment_no'];
        }

        return $return_arr;
    }

    static public function update_line_params( $current_data, $append_data )
    {
        if( empty( $append_data ) or (!is_array( $append_data ) and !is_string( $append_data )) )
            $append_data = array();
        if( empty( $current_data ) or (!is_array( $current_data ) and !is_string( $current_data )) )
            $current_data = array();

        if( !is_array( $append_data ) )
            $append_arr = self::parse_string( $append_data );
        else
            $append_arr = $append_data;

        if( !is_array( $current_data ) )
            $current_arr = self::parse_string( $current_data );
        else
            $current_arr = $current_data;

        if( !empty( $append_arr ) )
        {
            foreach( $append_arr as $key => $val )
                $current_arr[$key] = $val;
        }

        return $current_arr;
    }

    static function parse_db_date( $str )
    {
        $str = trim( $str );
        if( strstr( $str, ' ' ) )
        {
            $d = explode( ' ', $str );
            $date_ = explode( '-', $d[0] );
            $time_ = explode( ':', $d[1] );
        } else
            $date_ = explode( '-', $str );

        for( $i = 0; $i < 3; $i++ )
        {
            if( !isset( $date_[$i] ) )
                $date_[$i] = 0;
            if( isset( $time_ ) and !isset( $time_[$i] ) )
                $time_[$i] = 0;
        }

        if( !empty( $date_ ) and is_array( $date_ ) )
            foreach( $date_ as $key => $val )
                $date_[$key] = intval( $val );
        if( !empty( $time_ ) and is_array( $time_ ) )
            foreach( $time_ as $key => $val )
                $time_[$key] = intval( $val );

        if( isset( $time_ ) )
            return mktime( $time_[0], $time_[1], $time_[2], $date_[1], $date_[2], $date_[0] );
        else
            return mktime( 0, 0, 0, $date_[1], $date_[2], $date_[0] );
    }

    static function seconds_passed( $str )
    {
        return time() - self::parse_db_date( $str );
    }

    public static function mixed_to_string( $value )
    {
        if( is_bool( $value ) )
            return '('.gettype( $value ).') ['.($value?'true':'false').']';

        if( is_resource( $value ) )
            return '('.@get_resource_type( $value ).')';

        if( is_array( $value ) )
            return '(array) ['.count( $value ).']';

        if( !is_object( $value ) )
        {
            $return_str = '(' . gettype( $value ) . ') [';
            if( is_string( $value ) and strlen( $value ) > 100 )
                $return_str .= substr( $value, 0, 100 ) . '[...]';
            else
                $return_str .= $value;

            $return_str .= ']';

            return $return_str;
        }

        return '('.@get_class( $value ).')';
    }

    public static function var_dump( $var, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['level'] ) )
            $params['level'] = 0;
        if( !isset( $params['max_level'] ) )
            $params['max_level'] = 3;

        if( $params['level'] >= $params['max_level'] )
        {
            if( is_scalar( $var ) )
            {
                if( !empty( $params['level'] ) )
                    return $var;

                ob_start();
                var_dump( $var );
                return ob_get_clean();
            }

            return '[Max recursion lvl reached: ' . $params['max_level'] . '] (' . gettype( $var ).' '.self::mixed_to_string( $var ) . ')';
        }

        $new_params = $params;
        $new_params['level']++;

        if( is_array( $var ) )
        {
            $new_var = array();
            foreach( $var as $key => $arr_val )
                $new_var[$key] = self::var_dump( $arr_val, $new_params );
        } elseif( is_object( $var ) )
        {
            $new_var = new \stdClass();
            if( ($var_vars = get_object_vars( $var )) )
            {
                foreach( $var_vars as $key => $arr_val )
                    $new_var->$key = self::var_dump( $arr_val, $new_params );
            }
        } elseif( is_resource( $var ) )
            $new_var = 'Resource ('.@get_resource_type( $var ).')';
        else
            $new_var = $var;

        if( empty( $params['level'] ) )
        {
            ob_start();
            var_dump( $new_var );

            return ob_get_clean();
        }

        return $new_var;
    }
}
