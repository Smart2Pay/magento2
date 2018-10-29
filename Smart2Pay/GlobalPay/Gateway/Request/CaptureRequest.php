<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Smart2Pay\GlobalPay\Model\Config\Source\Environment;
use Magento\Checkout\Model\Session as CheckoutSession;

class CaptureRequest implements BuilderInterface
{
    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var \Smart2Pay\GlobalPay\Model\ConfiguredMethods */
    protected $_configuredMethods;

    /** @var \Smart2Pay\GlobalPay\Model\Country */
    protected $_country;

    /** @var CheckoutSession */
    protected $_checkoutSession;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $_timezone;

    /** @var ConfigInterface */
    private $config;

    /**
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper
     * @param ConfigInterface $config
     */
    public function __construct(
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $transactionFactory,
        \Smart2Pay\GlobalPay\Model\CountryFactory $s2pCountry,
        CheckoutSession $checkoutSession,
        \Smart2Pay\GlobalPay\Model\ConfiguredMethodsFactory $configuredMethodsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        ConfigInterface $config
    ) {
        $this->_s2pHelper = $s2pHelper;
        $this->_s2pTransaction = $transactionFactory;
        $this->_configuredMethods = $configuredMethodsFactory;
        $this->_country = $s2pCountry;
        $this->_checkoutSession = $checkoutSession;
        $this->_timezone = $timezone;
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build( array $buildSubject )
    {
        $s2p_helper = $this->_s2pHelper;

        ob_start();
        echo 'CaptureRequest IN';
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDataObject */
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment( $buildSubject );
        $payment = $paymentDataObject->getPayment();

        $flow_arr = $s2p_helper::default_payment_request_flow();

        if( !($config_arr = $s2p_helper->getFullConfigArray())
         or !($pay_info = $payment->getAdditionalInformation())
         or !is_array( $pay_info )
         or empty( $pay_info['environment'] )
         or empty( $pay_info['sp_method'] )
         or empty( $pay_info['country'] )
         or !($environment = Environment::validEnvironment( $pay_info['environment'] ))
         or !($country_id = $this->_country->create()->checkCode( $pay_info['country'] ))
         or !($enabled_methods = $this->_configuredMethods->create()->getConfiguredMethodsForCountryID( $country_id, $environment, [ 'id_in_index' => true ] ))
         or !is_array( $enabled_methods )
         or empty( $enabled_methods[$pay_info['sp_method']] ) or !is_array( $enabled_methods[$pay_info['sp_method']] ) )
        {
            $flow_arr['errors'][] = __( 'Error obtaining payment information. Please try again.' );

            return $flow_arr;
        }

        $flow_arr['payment'] = $payment;
        $flow_arr['s2p_method'] = $enabled_methods[$pay_info['sp_method']];

        $sdk_obj = $s2p_helper->getSDKHelper();

        $quote = $this->_checkoutSession->getQuote();

        $include_metod_ids = array_keys( $enabled_methods );
        $environment = $config_arr['environment'];

        $merchant_transaction_id = $quote->getReservedOrderId();
        if( $environment == Environment::ENV_DEMO )
            $merchant_transaction_id = $s2p_helper->convert_to_demo_merchant_transaction_id( $merchant_transaction_id );

        // if( !empty( $pay_info['sp_surcharge'] ) )
        //     $surcharge_amount = $pay_info['sp_surcharge'];
        // else
        //     $surcharge_amount = 0;
        // if( !empty( $pay_info['sp_fixed_amount'] ) )
        //     $surcharge_fixed_amount = $pay_info['sp_fixed_amount'];
        // else
        //     $surcharge_fixed_amount = 0;

        if( !($surcharge_amount = $quote->getPayment()->getS2pSurchargeAmount()) )
            $surcharge_amount = 0;
        if( !($surcharge_fixed_amount = $quote->getPayment()->getS2pSurchargeFixedAmount()) )
            $surcharge_fixed_amount = 0;

        $total_surcharge_amount = $surcharge_amount + $surcharge_fixed_amount;

        $order_original_amount = $amount_to_pay = $quote->getGrandTotal();

        $articles_params = array();
        $articles_params['transport_amount'] = $quote->getShippingAddress()->getShippingAmount(); // $order->getShippingAmount();
        $articles_params['total_surcharge'] = $total_surcharge_amount;
        $articles_params['amount_to_pay'] = $amount_to_pay;

        $order_products_arr = array();
        if( ($order_products = $quote->getItems())
        and is_array( $order_products ) )
        {
            /** @var \Magento\Quote\Api\Data\CartItemInterface $product_obj */
            foreach( $order_products as $product_obj )
            {
                $order_products_arr[] = $product_obj->getData();
            }
        }

        $original_amount = $articles_params['amount_to_pay'] - $articles_params['total_surcharge'];

        $articles_str = '';
        $sdk_articles_arr = array();
        $articles_diff = 0;
        if( ($articles_check = $s2p_helper::cart_products_to_string( $order_products_arr, $original_amount, $articles_params )) )
        {
            $articles_str = $articles_check['buffer'];
            $sdk_articles_arr = $articles_check['sdk_articles_arr'];

            if( !empty( $articles_check['total_difference_amount'] )
                and $articles_check['total_difference_amount'] >= -0.01 and $articles_check['total_difference_amount'] <= 0.01 )
            {
                $articles_diff = $articles_check['total_difference_amount'];

                //if( $pay_info['sp_method'] == self::PAYMENT_METHOD_KLARNA_CHECKOUT
                // or $pay_info['sp_method'] == self::PAYMENT_METHOD_KLARNA_INVOICE )
                //    $amount_to_pay += $articles_diff;
            }
        }

        $currency = $quote->getCurrency()->getStoreCurrencyCode();

        //
        // SDK functionality
        //
        $payment_arr = array();
        $payment_arr['merchanttransactionid'] = $merchant_transaction_id;
        $payment_arr['amount'] = number_format( $amount_to_pay, 2, '.', '' ) * 100;
        $payment_arr['currency'] = $currency;
        $payment_arr['methodid'] = $pay_info['sp_method'];

        if( empty( $pay_info['sp_method'] ) and !empty( $include_metod_ids ) )
            $payment_arr['includemethodids'] = $include_metod_ids;

        if( !empty( $config_arr['skin_id'] ) )
            $payment_arr['skinid'] = $config_arr['skin_id'];

        if( !empty( $config_arr['product_description_ref'] )
         or empty( $config_arr['product_description_custom'] ) )
            $payment_arr['description'] = 'Ref. no.: '.$merchant_transaction_id;
        else
            $payment_arr['description'] = $config_arr['product_description_custom'];

        if( ($remote_ip = $quote->getRemoteIp()) )
            $payment_arr['clientip'] = $remote_ip;

        $payment_arr['customer'] = array();
        $payment_arr['billingaddress'] = array();

        if( ($customer_fname = $quote->getBillingAddress()->getFirstname()) )
            $payment_arr['customer']['firstname'] = $customer_fname;
        if( ($customer_lname = $quote->getBillingAddress()->getLastname()) )
            $payment_arr['customer']['lastname'] = $customer_lname;
        if( ($customer_email = $quote->getCustomerEmail()) )
            $payment_arr['customer']['email'] = $customer_email;
        if( ($dateofbirth = $quote->getCustomerDob()) )
            $payment_arr['customer']['dateofbirth'] = $this->_timezone->date(new \DateTime( $dateofbirth ))->format( 'Ymd' );
        if( ($customer_phone = $quote->getBillingAddress()->getTelephone()) )
            $payment_arr['customer']['phone'] = $customer_phone;
        if( ($customer_company = $quote->getBillingAddress()->getCompany()) )
            $payment_arr['customer']['company'] = $customer_company;

        if( ($baddress_country = $quote->getBillingAddress()->getCountryId()) )
            $payment_arr['billingaddress']['country'] = $baddress_country;
        if( ($baddress_city = $quote->getBillingAddress()->getCity()) )
            $payment_arr['billingaddress']['city'] = $baddress_city;
        if( ($baddress_zip = $quote->getBillingAddress()->getPostcode()) )
            $payment_arr['billingaddress']['zipcode'] = $baddress_zip;
        if( ($baddress_state = $quote->getBillingAddress()->getRegion()) )
            $payment_arr['billingaddress']['state'] = $baddress_state;
        if( ($baddress_street = $quote->getBillingAddress()->getStreetFull()) )
            $payment_arr['billingaddress']['street'] = str_replace( "\n", ' ', $baddress_street );

        if( empty( $payment_arr['customer'] ) )
            unset( $payment_arr['customer'] );
        if( empty( $payment_arr['billingaddress'] ) )
            unset( $payment_arr['billingaddress'] );

        if( !empty( $sdk_articles_arr ) )
            $payment_arr['articles'] = $sdk_articles_arr;

        ob_start();
        echo 'CaptureRequest';
        echo $s2p_helper::var_dump( $payment_arr, array( 'max_level' => 5 ) );
        $buf = ob_get_clean();

        $s2p_helper->foobar( $buf );

        $flow_arr['payload'] = $payment_arr;

        return $flow_arr;
    }
}
