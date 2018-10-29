<?php

namespace Smart2Pay\GlobalPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Directory\Model;

class S2pSDK extends AbstractHelper
{
    const ERR_GENERIC = 1;

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper|bool */
    protected $_s2pHelper = false;

    /**
     * Country Method Factory
     *
     * @var \Smart2Pay\GlobalPay\Model\MethodFactory
     */
    private $_methodFactory;

    private static $_sdk_inited = false;

    private static $_error_msg = '';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Smart2Pay\GlobalPay\Model\MethodFactory $methodFactory
    ) {
        parent::__construct( $context );

        $this->_methodFactory = $methodFactory;

        self::_init_sdk();
    }

    public function s2p_helper( &$helper = false )
    {
        if( $helper === false )
            return $this->_s2pHelper;

        $this->_s2pHelper = $helper;
        return true;
    }

    private static function _init_sdk()
    {
        if( empty( self::$_sdk_inited )
            /**
        and @is_dir( __DIR__.'/sdk' )
        and @file_exists( __DIR__.'/sdk/bootstrap.php' )
             /**/
        )
        {
            //include_once( __DIR__.'/sdk/bootstrap.php' );

            // pretend we are working with config.php file...
            define( 'S2P_SDK_SITE_ID', '' );
            define( 'S2P_SDK_API_KEY', '' );
            define( 'S2P_SDK_ENVIRONMENT', '' );

            \S2P_SDK\S2P_SDK_Module::sdk_init();

            \S2P_SDK\S2P_SDK_Module::st_debugging_mode( false );
            \S2P_SDK\S2P_SDK_Module::st_detailed_errors( false );
            \S2P_SDK\S2P_SDK_Module::st_throw_errors( false );

            self::$_sdk_inited = true;
        }

        return self::$_sdk_inited;
    }

    public function get_error()
    {
        return self::$_error_msg;
    }

    public static function st_has_error()
    {
        return (!empty( self::$_error_msg ));
    }

    public function has_error()
    {
        return self::st_has_error();
    }

    private static function _st_set_error( $error_msg )
    {
        self::$_error_msg = $error_msg;
    }

    private function _set_error( $error_msg )
    {
        self::_st_set_error( $error_msg );
    }

    private static function _st_reset_error()
    {
        self::$_error_msg = '';
    }

    private function _reset_error()
    {
        self::_st_reset_error();
    }

    public static function get_sdk_version()
    {
        self::_st_reset_error();

        if( !self::_init_sdk() )
        {
            self::_st_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        if( !defined( 'S2P_SDK_VERSION' ) )
        {
            self::_st_set_error( 'Unknown Smart2Pay SDK version.' );
            return false;
        }

        return S2P_SDK_VERSION;
    }

    public function get_api_credentials()
    {
        if( !$this->s2p_helper() )
            return false;

        $s2p_helper = $this->s2p_helper();

        return $s2p_helper->get_api_credentials();
    }

    public function get_available_methods()
    {
        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $this->get_api_credentials();

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment']; // test or live

        $api_parameters['method'] = 'methods';
        $api_parameters['func'] = 'assigned_methods';

        $api_parameters['get_variables'] = array(
            'additional_details' => true,
        );
        $api_parameters['method_params'] = array();

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = \S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['methods'] ) or !is_array( $call_result['call_result']['methods'] ) )
        {
            if( ($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call failed while obtaining methods list.' );

            return false;
        }

        return $call_result['call_result']['methods'];
    }

    public function get_method_details( $method_id )
    {
        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $this->get_api_credentials();

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'methods';
        $api_parameters['func'] = 'method_details';

        $api_parameters['get_variables'] = array(
            'id' => $method_id,
        );
        $api_parameters['method_params'] = array();

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = \S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['method'] ) or !is_array( $call_result['call_result']['method'] ) )
        {
            if( ($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call failed while obtaining method details.' );

            return false;
        }

        return $call_result['call_result']['method'];
    }

    public function init_payment( $payment_details_arr )
    {
        $s2p_helper = $this->_s2pHelper;

        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $s2p_helper->get_api_credentials();

        if( !($method_settings = $s2p_helper->getFullConfigArray())
         or empty( $method_settings['return_url'] ) )
        {
            $this->_set_error( 'Return URL in plugin settings is invalid.' );
            return false;
        }

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'payments';
        $api_parameters['func'] = 'payment_init';

        $api_parameters['get_variables'] = array();
        $api_parameters['method_params'] = array( 'payment' => $payment_details_arr );

        if( empty( $api_parameters['method_params']['payment']['tokenlifetime'] ) )
            $api_parameters['method_params']['payment']['tokenlifetime'] = 15;

        $api_parameters['method_params']['payment']['returnurl'] = $method_settings['return_url'];

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = \S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['payment'] ) or !is_array( $call_result['call_result']['payment'] ) )
        {
            if( ($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call to initialize payment failed. Please try again.' );

            return false;
        }

        return $call_result['call_result']['payment'];
    }

    public function card_init_payment( $payment_details_arr )
    {
        $s2p_helper = $this->_s2pHelper;

        $this->_reset_error();

        if( !self::_init_sdk() )
        {
            $this->_set_error( 'Error initializing Smart2Pay SDK.' );
            return false;
        }

        $api_credentials = $s2p_helper->get_api_credentials();

        if( !($method_settings = $s2p_helper->getFullConfigArray())
         or empty( $method_settings['return_url'] ) )
        {
            $this->_set_error( 'Return URL in plugin settings is invalid.' );
            return false;
        }

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'cards';
        $api_parameters['func'] = 'payment_init';

        $api_parameters['get_variables'] = array();
        $api_parameters['method_params'] = array( 'payment' => $payment_details_arr );

        if( empty( $api_parameters['method_params']['payment']['tokenlifetime'] ) )
            $api_parameters['method_params']['payment']['tokenlifetime'] = 15;

        if( !isset( $api_parameters['method_params']['payment']['capture'] ) )
            $api_parameters['method_params']['payment']['capture'] = true;
        if( !isset( $api_parameters['method_params']['payment']['retry'] ) )
            $api_parameters['method_params']['payment']['retry'] = false;
        if( !isset( $api_parameters['method_params']['payment']['3dsecure'] ) )
            $api_parameters['method_params']['payment']['3dsecure'] = true;
        if( !isset( $api_parameters['method_params']['payment']['generatecreditcardtoken'] ) )
            $api_parameters['method_params']['payment']['generatecreditcardtoken'] = false;

        $api_parameters['method_params']['payment']['returnurl'] = $method_settings['return_url'];

        $call_params = array();

        $finalize_params = array();
        $finalize_params['redirect_now'] = false;

        if( !($call_result = \S2P_SDK\S2P_SDK_Module::quick_call( $api_parameters, $call_params, $finalize_params ))
         or empty( $call_result['call_result'] ) or !is_array( $call_result['call_result'] )
         or empty( $call_result['call_result']['payment'] ) or !is_array( $call_result['call_result']['payment'] ) )
        {
            if( ($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            and !empty( $error_arr['display_error'] ) )
                $this->_set_error( $error_arr['display_error'] );
            else
                $this->_set_error( 'API call to initialize card payment failed. Please try again.' );

            return false;
        }

        return $call_result['call_result']['payment'];
    }

    public function refresh_available_methods()
    {
        $methodFactory = $this->_methodFactory;

        $this->_reset_error();

        if( !$this->s2p_helper() )
        {
            $this->_set_error( 'Couldn\'t initialize payment module.' );
            return false;
        }

        $s2p_helper = $this->s2p_helper();

        if( false and ($seconds_to_sync = $s2p_helper->seconds_to_launch_sync_str()) )
        {
            $this->_set_error( 'You can syncronize methods once every '.$s2p_helper::RESYNC_AFTER_HOURS.' hours. Time left: '.$seconds_to_sync );
            return false;
        }

        if( !($available_methods = $this->get_available_methods())
         or !is_array( $available_methods ) )
        {
            if( !$this->has_error() )
                $this->_set_error( 'Couldn\'t obtain a list of methods.' );
            return false;
        }

        if( true !== ($error_msg = $methodFactory->create()->saveMethodsFromSDKResponse( $available_methods, $s2p_helper->getEnvironment() )) )
        {
            $this->_set_error( $error_msg );
            return false;
        }

        $s2p_helper->last_methods_sync_option( false );

        return true;
    }
}

