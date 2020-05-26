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

    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper */
    protected $s2pHelper;

    /** @var \Magento\Backend\Model\UrlInterface */
    protected $backendUrl;

    /** @var \Magento\Framework\Url */
    protected $frontUrl;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Url $frontUrl,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $s2pHelper,
        array $data = []
    ) {
        $this->s2pHelper = $s2pHelper;
        $this->frontUrl = $frontUrl;
        $this->backendUrl = $backendUrl;

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

    public function getRegistrationNotificationOption()
    {
        return $this->s2pHelper->getRegistrationNotificationOption();
    }

    public function canRegisterNewAccountForThisPlatform()
    {
        return $this->s2pHelper->canRegisterNewAccountForThisPlatform();
    }

    public function getNotificationURL()
    {
        $params = ['nounce'=>$this->s2pHelper->getRegistrationNotificationNounce(),'_nosid' => true];
        $url = $this->frontUrl->getUrl('smart2pay/payment/registration', $params);

        if (false === strpos($url, '?')) {
            $url .= '?';
        }

        //$url .= '&__json_data=1';
        $url .= '&__send_get=1';

        return $url;
    }

    public function getReturnURL()
    {
        $params = ['_nosid' => true];
        return $this->backendUrl->getRouteUrl('admin/admin/system_config/edit/section/payment', $params);
        //return $this->backendUrl->turnOffSecretKey()->getUrl('admin/admin/system_config/edit/section/payment', $params);
    }

    public function getRegistrationLink()
    {
        $notification_url = $this->getNotificationURL();
        $return_url = $this->getReturnURL();

        return 'https://www.smart2pay.com/microsoft/signup/?'.
               'utm_medium=affiliates&utm_source=magento&utm_campaign=premium_partnership'.
               '&notification_url='.urlencode($notification_url).
               '&return_url='.urlencode($return_url);
    }
}
