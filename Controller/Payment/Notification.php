<?php

namespace Smart2Pay\GlobalPay\Controller\Payment;

use Magento\Framework\App\Request\Http;

class Notification extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        // Ugly bug when sending POST data to a script...
        if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
            $request = $this->getRequest();
            if ($request instanceof Http
            && $request->isPost() ) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }

    /**
     * Load the page defined in view/frontend/layout/smart2pay_payment_notification.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
