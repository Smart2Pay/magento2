/*jshint browser:true*/
/*global alert*/
/**
 * Smart2Pay checkout adapter for customer data storage
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, storage) {
    'use strict';

    var defaultCheckData = {
        'selectedS2PMethod': 0
    };

    var cacheKey = 's2p-checkout-data';

    var getData = function () {
        return $.extend( true, storage.get(cacheKey), defaultCheckData );
    };

    var saveData = function ( checkoutData )
    {
        $.extend( true, checkoutData, defaultCheckData );

        storage.set( cacheKey, checkoutData );
    };

    if ($.isEmptyObject(getData())) {
        saveData( defaultCheckData );
    }

    return {
        setSelectedS2PMethod: function ( methodId ) {
            var obj = getData();
            obj.selectedS2PMethod = methodId;

            saveData( obj );
        },

        getSelectedS2PMethod: function () {
            return getData().selectedS2PMethod;
        }
    }
});
