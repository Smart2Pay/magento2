<?php

namespace Smart2Pay\GlobalPay\Block\Adminhtml\System\Config\Field;

class Registration extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'registration.phtml';

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper $_s2pHelper */
    protected $s2pHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        array $data = []
    ) {
        $this->s2pHelper = $s2pHelper;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setRenderer($this);
        return $this->_toHtml();
    }

    public function getPluginSettings()
    {
        return $this->s2pHelper->getFullConfigArray();
    }

    public function getRegistrationLink()
    {
        return '#';
    }
}
