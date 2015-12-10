<?php

namespace Smart2Pay\GlobalPay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Displaymode implements ArrayInterface
{
    const MODE_LOGO = 'logo', MODE_TEXT = 'text', MODE_BOTH = 'both';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::MODE_LOGO,
                'label' => __('Logo'),
            ],
            [
                'value' => self::MODE_TEXT,
                'label' => __('Text'),
            ],
            [
                'value' => self::MODE_BOTH,
                'label' => __('Logo and Text'),
            ],
        ];
    }
}
