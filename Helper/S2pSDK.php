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
        parent::__construct($context);

        $this->_methodFactory = $methodFactory;

        self::initSDK();
    }

    public function s2pHelper(&$helper = false)
    {
        if ($helper === false) {
            return $this->_s2pHelper;
        }

        $this->_s2pHelper = $helper;
        return true;
    }

    private static function initSDK()
    {
        if (empty(self::$_sdk_inited)
            /**
        && @is_dir( __DIR__.'/sdk' )
        && @file_exists( __DIR__.'/sdk/bootstrap.php' )
             /**/
        ) {
            //include_once( __DIR__.'/sdk/bootstrap.php' );

            // pretend we are working with config.php file...
            define('S2P_SDK_SITE_ID', '');
            define('S2P_SDK_API_KEY', '');
            define('S2P_SDK_ENVIRONMENT', '');

            \S2P_SDK\S2P_SDK_Module::sdk_init();

            \S2P_SDK\S2P_SDK_Module::st_debugging_mode(false);
            \S2P_SDK\S2P_SDK_Module::st_detailed_errors(false);
            \S2P_SDK\S2P_SDK_Module::st_throw_errors(false);

            self::$_sdk_inited = true;
        }

        return self::$_sdk_inited;
    }

    public function getError()
    {
        return self::$_error_msg;
    }

    public static function stHasError()
    {
        return (!empty(self::$_error_msg));
    }

    public function hasError()
    {
        return self::stHasError();
    }

    private static function stSetError($error_msg)
    {
        self::$_error_msg = $error_msg;
    }

    private function setError($error_msg)
    {
        self::stSetError($error_msg);
    }

    private static function stResetError()
    {
        self::$_error_msg = '';
    }

    private function resetError()
    {
        self::stResetError();
    }

    public static function getSDKVersion()
    {
        self::stResetError();

        if (!self::initSDK()) {
            self::stSetError('Error initializing Smart2Pay SDK.');
            return false;
        }

        if (!defined('S2P_SDK_VERSION')) {
            self::stSetError('Unknown Smart2Pay SDK version.');
            return false;
        }

        return S2P_SDK_VERSION;
    }

    public function getAPICredentials()
    {
        if (!$this->s2pHelper()) {
            return false;
        }

        $s2p_helper = $this->s2pHelper();

        return $s2p_helper->getAPICredentials();
    }

    public function getAvailableMethods()
    {
        $this->resetError();

        if (!self::initSDK()) {
            $this->setError('Error initializing Smart2Pay SDK.');
            return false;
        }

        $api_credentials = $this->getAPICredentials();

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment']; // test or live

        $api_parameters['method'] = 'methods';
        $api_parameters['func'] = 'assigned_methods';

        $api_parameters['get_variables'] = [
            'additional_details' => true,
        ];
        $api_parameters['method_params'] = [];

        $call_params = [];

        $finalize_params = [];
        $finalize_params['redirect_now'] = false;

        if (!($call_result = \S2P_SDK\S2P_SDK_Module::quick_call($api_parameters, $call_params, $finalize_params))
         || empty($call_result['call_result']) || !is_array($call_result['call_result'])
         || empty($call_result['call_result']['methods']) || !is_array($call_result['call_result']['methods'])) {
            if (($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            && !empty($error_arr['display_error'])) {
                $this->setError($error_arr['display_error']);
            } else {
                $this->setError('API call failed while obtaining methods list.');
            }

            return false;
        }

        return $call_result['call_result']['methods'];
    }

    public function getMethodDetails($method_id)
    {
        $this->resetError();

        if (!self::initSDK()) {
            $this->setError('Error initializing Smart2Pay SDK.');
            return false;
        }

        $api_credentials = $this->getAPICredentials();

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'methods';
        $api_parameters['func'] = 'method_details';

        $api_parameters['get_variables'] = [
            'id' => $method_id,
        ];
        $api_parameters['method_params'] = [];

        $call_params = [];

        $finalize_params = [];
        $finalize_params['redirect_now'] = false;

        if (!($call_result = \S2P_SDK\S2P_SDK_Module::quick_call($api_parameters, $call_params, $finalize_params))
         || empty($call_result['call_result']) || !is_array($call_result['call_result'])
         || empty($call_result['call_result']['method']) || !is_array($call_result['call_result']['method'])) {
            if (($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            && !empty($error_arr['display_error'])) {
                $this->setError($error_arr['display_error']);
            } else {
                $this->setError('API call failed while obtaining method details.');
            }

            return false;
        }

        return $call_result['call_result']['method'];
    }

    public function initPayment($payment_details_arr)
    {
        $s2p_helper = $this->_s2pHelper;

        $this->resetError();

        if (!self::initSDK()) {
            $this->setError('Error initializing Smart2Pay SDK.');
            return false;
        }

        $api_credentials = $s2p_helper->getAPICredentials();

        if (!($method_settings = $s2p_helper->getFullConfigArray())
         || empty($method_settings['return_url'])) {
            $this->setError('Return URL in plugin settings is invalid.');
            return false;
        }

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'payments';
        $api_parameters['func'] = 'payment_init';

        $api_parameters['get_variables'] = [];
        $api_parameters['method_params'] = [ 'payment' => $payment_details_arr ];

        if (empty($api_parameters['method_params']['payment']['tokenlifetime'])) {
            $api_parameters['method_params']['payment']['tokenlifetime'] = 15;
        }

        $api_parameters['method_params']['payment']['returnurl'] = $method_settings['return_url'];

        $call_params = [];

        $finalize_params = [];
        $finalize_params['redirect_now'] = false;

        if (!($call_result = \S2P_SDK\S2P_SDK_Module::quick_call($api_parameters, $call_params, $finalize_params))
         || empty($call_result['call_result']) || !is_array($call_result['call_result'])
         || empty($call_result['call_result']['payment']) || !is_array($call_result['call_result']['payment'])) {
            if (($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            && !empty($error_arr['display_error'])) {
                $this->setError($error_arr['display_error']);
            } else {
                $this->setError('API call to initialize payment failed. Please try again.');
            }

            return false;
        }

        return $call_result['call_result']['payment'];
    }

    public function cardInitPayment($payment_details_arr)
    {
        $s2p_helper = $this->_s2pHelper;

        $this->resetError();

        if (!self::initSDK()) {
            $this->setError('Error initializing Smart2Pay SDK.');
            return false;
        }

        $api_credentials = $s2p_helper->getAPICredentials();

        if (!($method_settings = $s2p_helper->getFullConfigArray())
         || empty($method_settings['return_url'])) {
            $this->setError('Return URL in plugin settings is invalid.');
            return false;
        }

        $api_parameters['api_key'] = $api_credentials['api_key'];
        $api_parameters['site_id'] = $api_credentials['site_id'];
        $api_parameters['environment'] = $api_credentials['environment'];

        $api_parameters['method'] = 'cards';
        $api_parameters['func'] = 'payment_init';

        $api_parameters['get_variables'] = [];
        $api_parameters['method_params'] = [ 'payment' => $payment_details_arr ];

        if (empty($api_parameters['method_params']['payment']['tokenlifetime'])) {
            $api_parameters['method_params']['payment']['tokenlifetime'] = 15;
        }

        if (!isset($api_parameters['method_params']['payment']['capture'])) {
            $api_parameters['method_params']['payment']['capture'] = true;
        }
        if (!isset($api_parameters['method_params']['payment']['retry'])) {
            $api_parameters['method_params']['payment']['retry'] = false;
        }
        if (!isset($api_parameters['method_params']['payment']['3dsecure'])) {
            $api_parameters['method_params']['payment']['3dsecure'] = true;
        }
        if (!isset($api_parameters['method_params']['payment']['generatecreditcardtoken'])) {
            $api_parameters['method_params']['payment']['generatecreditcardtoken'] = false;
        }

        $api_parameters['method_params']['payment']['returnurl'] = $method_settings['return_url'];

        $call_params = [];

        $finalize_params = [];
        $finalize_params['redirect_now'] = false;

        if (!($call_result = \S2P_SDK\S2P_SDK_Module::quick_call($api_parameters, $call_params, $finalize_params))
         || empty($call_result['call_result']) || !is_array($call_result['call_result'])
         || empty($call_result['call_result']['payment']) || !is_array($call_result['call_result']['payment'])) {
            if (($error_arr = \S2P_SDK\S2P_SDK_Module::st_get_error())
            && !empty($error_arr['display_error'])) {
                $this->setError($error_arr['display_error']);
            } else {
                $this->setError('API call to initialize card payment failed. Please try again.');
            }

            return false;
        }

        return $call_result['call_result']['payment'];
    }

    public function refreshAvailableMethods()
    {
        $methodFactory = $this->_methodFactory;

        $this->resetError();

        if (!$this->s2pHelper()) {
            $this->setError('Couldn\'t initialize payment module.');
            return false;
        }

        $s2p_helper = $this->s2pHelper();

        if (($seconds_to_sync = $s2p_helper->secondsToLaunchSyncStr())) {
            $this->setError(
                'You can syncronize methods once every '.
                $s2p_helper::RESYNC_AFTER_HOURS.' hours. Time left: '.$seconds_to_sync
            );
            return false;
        }

        if (!($available_methods = $this->getAvailableMethods())
         || !is_array($available_methods)) {
            if (!$this->hasError()) {
                $this->setError('Couldn\'t obtain a list of methods.');
            }
            return false;
        }

        if (true !== ($error_msg = $methodFactory->create()
                                                 ->saveMethodsFromSDKResponse(
                                                     $available_methods,
                                                     $s2p_helper->getEnvironment()
                                                 ))) {
            $this->setError($error_msg);
            return false;
        }

        $s2p_helper->lastMethodsSyncOption(false);

        return true;
    }
}
