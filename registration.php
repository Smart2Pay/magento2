<?php

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Smart2Pay_GlobalPay',
    __DIR__ // isset($file) ? dirname($file) : __DIR__ // __DIR__
);
