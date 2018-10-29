<?php
namespace Smart2Pay\GlobalPay\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Smart2Pay\GlobalPay\Gateway\Http\Client\PaymentInit;

class RequestValidator extends AbstractValidator
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
        //$response = \Magento\Payment\Gateway\Helper\SubjectReader::readResponse( $validationSubject );
        $s2p_helper = $this->_s2pHelper;

        ob_start();
        echo 'RequestValidator';
        echo $s2p_helper::var_dump( $validationSubject, array( 'max_level' => 5 ) );
        $buf = ob_get_clean();

        $this->_s2pHelper->foobar( $buf );

        return $this->createResult(
            true,
            []
        );

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true,
                []
            );
        } else {
            return $this->createResult(
                false,
                [__('RequestValidator failed.')]
            );
        }
    }
}
