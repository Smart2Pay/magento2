/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'smart2pay',
                component: 'Smart2Pay_GlobalPay/js/view/payment/method-renderer/smart2pay'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
