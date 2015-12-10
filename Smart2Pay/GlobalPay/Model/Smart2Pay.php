<?php

namespace Smart2Pay\GlobalPay\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data;

/**
 * Class Smart2Pay
 */
class Smart2Pay extends AbstractMethod
{
    const METHOD_CODE = 'smart2pay';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    protected $_canUseInternal = false;

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

    /**
     * To check billing country is allowed for the payment method
     *
     * @param string $country
     * @return bool
     */
    public function canUseForCountry( $country )
    {
        //! TODO: Add country check here...
        return true;
    }

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

}
