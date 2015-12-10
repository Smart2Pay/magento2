<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Smart2Pay\GlobalPay\Block\Info;

class Smart2Pay extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_instructions;

    /**
     * @var string
     */
    protected $_template = 'Smart2Pay_GlobalPay::info/smart2pay.phtml';

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getInstructions()
    {
        if ($this->_instructions === null) {
            $this->_convertAdditionalData();
        }
        return $this->_instructions;
    }

    /**
     * Enter description here...
     *
     * @return $this
     */
    protected function _convertAdditionalData()
    {
        $details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_instructions = isset($details['instructions']) ? (string)$details['instructions'] : '';
        } else {
            $this->_instructions = '';
        }
        return $this;
    }

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Smart2Pay_GlobalPay::info/pdf/smart2pay.phtml');
        return $this->toHtml();
    }
}
