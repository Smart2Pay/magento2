<?php

namespace Smart2Pay\GlobalPay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Environment implements ArrayInterface
{
    const ENV_DEMO = 'demo', ENV_TEST = 'test', ENV_LIVE = 'live';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENV_DEMO,
                'label' => __('Demo'),
            ],
            [
                'value' => self::ENV_TEST,
                'label' => __('Test'),
            ],
            [
                'value' => self::ENV_LIVE,
                'label' => __('Live'),
            ],
        ];
    }

    public static function validEnvironment( $env )
    {
        $env = strtolower( trim( $env ) );
        if( !in_array( $env, [ self::ENV_DEMO, self::ENV_TEST, self::ENV_LIVE ] ) )
            return false;

        return $env;
    }
}
