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

    var cacheKey = 's2p-checkout-data';

    var getData = function () {
        return storage.get(cacheKey)();
    };

    var saveData = function (checkoutData) {
        storage.set(cacheKey, checkoutData);
    };

    if ($.isEmptyObject(getData())) {
        var checkoutData = {
            'selectedS2PMethod': 0
        };
        saveData(checkoutData);
    }

    return {
        setSelectedS2PMethod: function (data) {
            var obj = getData();
            obj.selectedS2PMethod = data;
            saveData(obj);
        },

        getSelectedS2PMethod: function () {
            return getData().selectedS2PMethod;
        }
    }
});
