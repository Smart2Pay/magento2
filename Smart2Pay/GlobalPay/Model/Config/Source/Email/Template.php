<?php

namespace Smart2Pay\GlobalPay\Model\Config\Source\Email;

use \Magento\Config\Model\Config\Source\Email;

class Template extends \Magento\Config\Model\Config\Source\Email\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $_coreRegistry;

    /**
     * @var \Magento\Email\Model\Template\Config
     */
    private $_emailConfig;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory
     * @param \Magento\Email\Model\Template\Config $emailConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory,
        \Magento\Email\Model\Template\Config $emailConfig,
        array $data = []
    ) {
        parent::__construct( $coreRegistry, $templatesFactory, $emailConfig, $data );

        $this->_coreRegistry = $coreRegistry;
        $this->_emailConfig = $emailConfig;
    }

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var $collection \Magento\Email\Model\ResourceModel\Template\Collection */
        if( !($collection = $this->_coreRegistry->registry( 'config_system_email_template' )) )
        {
            $collection = $this->_templatesFactory->create();
            $collection->load();
            $this->_coreRegistry->register( 'config_system_email_template', $collection );
        }

        $options = $collection->toOptionArray();

        $template_id = '';
        if( ($path_arr = explode( '/', $this->getPath() )) )
            $template_id = array_pop( $path_arr );

        $templateLabel = $this->_emailConfig->getTemplateLabel( $template_id );
        $templateLabel = __( '%1 (Default)', $templateLabel );

        array_unshift( $options, ['value' => $template_id, 'label' => $templateLabel ] );
        return $options;
    }
}
