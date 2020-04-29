<?php
namespace Smart2Pay\GlobalPay\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Smart2Pay\GlobalPay\Gateway\Http\Client\PaymentInit;

class ResponsePaymentValidator extends AbstractValidator
{
    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $_s2pHelper;

    /**
     * @param \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct( $resultFactory );

        $this->_s2pHelper = $s2pHelper;
    }

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $s2p_helper = $this->_s2pHelper;

        if( !($response = \Magento\Payment\Gateway\Helper\SubjectReader::readResponse( $validationSubject ))
         or !is_array( $response ) )
        {
            return $this->createResult(
                false,
                [
                    __( 'Error parsing response from server.' )
                ]
            );
        }

        if( !empty( $response['errors'] ) and is_array( $response['errors'] ) )
        {
            return $this->createResult(
                false,
                $response['errors']
            );
        }

        return $this->createResult(
            true,
            []
        );
    }
}
