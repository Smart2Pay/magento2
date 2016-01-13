<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Block\Info;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Smart2Pay extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_instructions;

    /** @var \Smart2Pay\GlobalPay\Model\TransactionFactory */
    protected $_s2pTransaction;

    /** @var \Smart2Pay\GlobalPay\Model\MethodFactory */
    protected $_s2pMethod;

    /** @var \Smart2Pay\GlobalPay\Helper\Smart2Pay */
    protected $_helper;

    function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Smart2Pay\GlobalPay\Model\TransactionFactory $s2pTransaction,
        \Smart2Pay\GlobalPay\Model\MethodFactory $s2pMethod,
        \Smart2Pay\GlobalPay\Helper\Smart2Pay $helperSmart2Pay,
        array $data = []
    )
    {
        parent::__construct( $context, $data );

        $this->_helper = $helperSmart2Pay;
        $this->_s2pTransaction = $s2pTransaction;
        $this->_s2pMethod = $s2pMethod;
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param null|\Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation( $transport = null )
    {
        if( null !== $this->_paymentSpecificInformation )
            return $this->_paymentSpecificInformation;

        $transport = parent::_prepareSpecificInformation( $transport );

        $module_name = $this->getRequest()->getModuleName();
        $action_name = $this->getRequest()->getActionName();

        if( ($details_arr = $this->getInfo()->getAdditionalInformation())
        and !empty( $details_arr['sp_method'] )
        and ($method_obj = $this->_s2pMethod->create()->load( $details_arr['sp_method'] )) )
        {
            if( !empty( $details_arr['sp_transaction'] )
            and ($transaction_obj = $this->_s2pTransaction->create()->load( $details_arr['sp_transaction'] )) )
            {
                if( !($payment_id = $transaction_obj->getPaymentId()) )
                    $payment_id = __( 'N/A' )->render();

                $this->_paymentSpecificInformation->setData( __( 'Method' )->render(), $method_obj->getDisplayName() );
                $this->_paymentSpecificInformation->setData( __( 'Environment' )->render(), $transaction_obj->getEnvironment() );
                $this->_paymentSpecificInformation->setData( __( 'Payment ID' )->render(), $payment_id );

                if( ($transaction_extra_arr = $transaction_obj->getExtraDataArray())
                and ($details_titles_arr = $this->_helper->transaction_logger_params_to_title()) )
                {
                    foreach( $transaction_extra_arr as $title_key => $val )
                    {
                        if( empty( $details_titles_arr[$title_key] ) )
                            continue;

                        $this->_paymentSpecificInformation->setData( $details_titles_arr[$title_key], $val );
                    }
                }
            }
        }

        $this->_paymentSpecificInformation->setData( 'Test', 'Test value' );

        return $this->_paymentSpecificInformation;
    }
}
