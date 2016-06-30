<?php

namespace Smart2Pay\GlobalPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Directory\Model;

class Smart2Pay extends AbstractHelper
{
    /**
     * Currency Factory
     *
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    //protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
        //, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_currencyFactory = $currencyFactory;
        parent::__construct( $context );

        //$this->_scopeConfig = $scopeConfig;
    }

    public function getBaseCurrencies()
    {
        $currency = $this->_currencyFactory->create();
        return $currency->getConfigBaseCurrencies();
    }

    public function getStoreName()
    {
        return $this->scopeConfig->getValue( 'general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE );
    }

    public function getStoreConfig( $path )
    {
        return $this->scopeConfig->getValue( $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE );
    }

    public function s2p_mb_substr( $message, $start, $length )
    {
        if( function_exists( 'mb_substr' ) )
            return mb_substr( $message, $start, $length, 'UTF-8' );
        else
            return substr( $message, $start, $length );
    }

    public function s2p_mb_strtolower( $message )
    {
        if( function_exists( 'mb_strtolower' ) )
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

    public function computeSHA256Hash( $message )
    {
        return hash( 'sha256', $this->s2p_mb_strtolower( $message ) );
    }

    public static function transaction_logger_params_to_title()
    {
        static $return_arr = [];

        if( !empty( $return_arr ) )
            return $return_arr;

        $return_arr = [
            'AccountHolder' => __( 'Account Holder' )->render(),
            'BankName' => __( 'Bank Name' )->render(),
            'AccountNumber' => __( 'Account Number' )->render(),
            'IBAN' => __( 'IBAN' )->render(),
            'SWIFT_BIC' => __( 'SWIFT / BIC' )->render(),
            'AccountCurrency' => __( 'Account Currency' )->render(),

            'EntityNumber' => __( 'Entity Number' )->render(),

            'ReferenceNumber' => __( 'Reference Number' )->render(),
            'AmountToPay' => __( 'Amount To Pay' )->render(),
        ];

        return $return_arr;
    }

    /**
     * Keys in returning array should be variable names sent back by Smart2Pay and values should be default values if
     * variables are not found in request
     *
     * @return array
     */
    public static function defaultTransactionLoggerExtraParams()
    {
        return array(
            // Method ID 1 (Bank transfer)
            'AccountHolder' => '',
            'BankName' => '',
            'AccountNumber' => '',
            'IBAN' => '',
            'SWIFT_BIC' => '',
            'AccountCurrency' => '',

            // Method ID 20 (Multibanco SIBS)
            'EntityNumber' => '',

            // Common to method id 20 and 1
            'ReferenceNumber' => '',
            'AmountToPay' => '',
        );
    }

    public static function validateTransactionLoggerExtraParams( $params_arr )
    {
        if( empty( $params_arr ) or !is_array( $params_arr ) )
            return array();

        $default_values = self::defaultTransactionLoggerExtraParams();
        $new_params_arr = array();
        foreach( $default_values as $key => $val )
        {
            if( !array_key_exists( $key, $params_arr ) )
                continue;

            if( is_int( $val ) )
                $new_val = (int)$params_arr[$key];
            elseif( is_string( $val ) )
                $new_val = trim( $params_arr[$key] );
            else
                $new_val = $params_arr[$key];

            if( $new_val === $val )
                continue;

            $new_params_arr[$key] = $new_val;
        }

        return $new_params_arr;
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

}
