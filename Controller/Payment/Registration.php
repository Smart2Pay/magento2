<?php

namespace Smart2Pay\GlobalPay\Controller\Payment;

use \Smart2Pay\GlobalPay\Model\Config\Source\Environment;

class Registration extends \Magento\Framework\App\Action\Action
{
    /** @var \Smart2Pay\GlobalPay\Helper\S2pHelper */
    protected $helper;

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    protected $json;

    /** @var \Magento\Framework\App\Cache\TypeList|\Magento\Framework\App\Cache\TypeList */
    protected $_cacheTypeList;

    /** @var \Magento\Framework\App\Cache\Frontend\Pool  */
    protected $_cacheFrontendPool;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Smart2Pay\GlobalPay\Helper\S2pHelper $helperSmart2Pay
    ) {
        parent::__construct($context);

        $this->helper = $helperSmart2Pay;
        $this->json = $json;

        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;

        // Ugly bug when sending POST data to a script...
        if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
            $request = $this->getRequest();
            if ($request instanceof Http
            && $request->isPost()) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $helper_obj = $this->helper;
        $json_obj = $this->json;

        if (!($nounce = $helper_obj->getParam('nounce', ''))
         || !$helper_obj->checkRegistrationNotificationNounce($nounce)) {
            return $this->sendResponseError('Invalid request.', 400);
        }

        if (!($site_id = $helper_obj->getParam('site_id', ''))
         || !($apikey = $helper_obj->getParam('apikey', ''))) {
            if (($input = file_get_contents('php://input')) === false
             || !($json_arr = $json_obj->unserialize($input))
             || empty($json_arr['site_id'])
             || empty($json_arr['apikey'])) {
                return $this->sendResponseError(
                    'Invalid parameters.',
                    400
                );
            }

            $site_id = $json_arr['site_id'];
            $apikey = $json_arr['apikey'];
        }

        if ($helper_obj->getRegistrationNotificationOption()) {
            return $this->sendResponseOk('Notification already processed.');
        }

        if ($helper_obj->getTestEnvironmentSettingsSet()) {
            return $this->sendResponseOk('TEST environment already set.');
        }

        $helper_obj->upateRegistrationNotificationSettings($site_id, $apikey);

        $types = [ 'config', 'config_integration', 'config_integration_api', 'config_webservice' ];

        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }

        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return $this->sendResponseOk();
    }

    /**
     * Send OK response
     * @param string $message Extra message (if any)
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function sendResponseOk($message = '')
    {
        $helper_obj = $this->helper;
        $json_obj = $this->json;

        $json_arr = [
            'ok' => true,
            'message' => $message,
            'notification_url' => $helper_obj->getPaymentNotificationURL(),
        ];

        return $this->getResponse()
            ->clearHeader('Content-Type')
            ->setHeader('Content-Type', 'application/json')
            ->setBody($json_obj->serialize($json_arr))
            ->setHttpResponseCode(200);
    }

    /**
     * Send Error response with message
     *
     * @param string $message
     * @param int $httpCode
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function sendResponseError($message, $httpCode = 0)
    {
        $json_obj = $this->json;

        $json_arr = [
            'ok' => false,
            'message' => $message,
        ];

        $this->getResponse()
            ->clearHeader('Content-Type')
            ->setHeader('Content-Type', 'application/json')
            ->setBody($json_obj->serialize($json_arr));

        if ($httpCode!==0) {
            $this->getResponse()->setHttpResponseCode($httpCode);
        }

        return $this->getResponse();
    }
}
