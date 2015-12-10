<?php
/**
 * Smart2Pay Observer
 */
namespace Smart2Pay\GlobalPay\Observer;

use Magento\Framework\Event\ObserverInterface;

class BeforeOrderPaymentSaveObserver implements ObserverInterface
{
    /**
     * Sets current instructions for bank transfer account
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute( \Magento\Framework\Event\Observer $observer )
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getPayment();

        $payment->setAdditionalInformation(
            'instructions',
            $payment->getMethodInstance()->getInstructions()
        );
    }
}
