define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Smart2Pay_GlobalPay/js/model/payment/method-list'
    ],
    function (ko, Component, quote, methodsList) {
        'use strict';

        return Component.extend({

            defaults: {
                template: 'Smart2Pay_GlobalPay/payment/smart2pay'
            },

            // Overwrite properties / functions

            redirectAfterPlaceOrder: false,

            isS2PPlaceOrderActionAllowed: ko.observable(false),

            // END Overwrite properties / functions

            selectedS2PPaymentMethod: ko.observable(0),
            selectedS2PCountry: ko.observable( '' ),

            addressSubscription: 0,

            getSelectedS2PCountry: function()
            {
                return this.selectedS2PCountry();
            },

            selectS2PPaymentMethod: function( method, method_obj )
            {
                console.log( 'Method [' + method + ']' );

                this.selectedS2PPaymentMethod( method );

                if( method == 0 )
                {
                    this.isS2PPlaceOrderActionAllowed( false );
                } else
                {
                    this.isS2PPlaceOrderActionAllowed( this.selectedS2PCountry() != '' );
                }
            },

            /**
             * Get payment method data
             */
            getData: function() {

                var self = this;

                return {
                    "method": this.item.method,
                    "po_number": null,
                    "additional_data": {
                        "s2p_method": self.selectedS2PPaymentMethod()
                    }
                };
            },

            updateCurrentCountry: function()
            {
                var new_country = '';
                if( quote.billingAddress() && quote.billingAddress().countryId )
                    new_country = quote.billingAddress().countryId;

                console.log( 'Check country [' + this.selectedS2PCountry() + '] != [' + new_country + ']' );

                if( (new_country == '' && this.isS2PPlaceOrderActionAllowed())
                 || !this.selectedS2PPaymentMethod() )
                    this.isS2PPlaceOrderActionAllowed( false );

                if( this.selectedS2PCountry() != new_country )
                {
                    console.log( 'NEW country [' + new_country + ']' );
                    this.selectedS2PCountry( new_country );
                    this.refreshMethods( new_country );
                }
            },

            getSmart2PayData: function ()
            {
                var self = this;

                if( this.addressSubscription )
                    this.addressSubscription.dispose();
                else
                    this.updateCurrentCountry();

                this.addressSubscription = quote.billingAddress.subscribe(function () {
                    self.updateCurrentCountry();
                });

                return '';
            },

            refreshMethods: function( country )
            {
                var self = this;

                methodsList([]);

                this.isS2PPlaceOrderActionAllowed( false );

                if( country
                 && window.checkoutConfig
                 && window.checkoutConfig.payment
                 && window.checkoutConfig.payment.smart2pay
                 && window.checkoutConfig.payment.smart2pay.methods
                 && window.checkoutConfig.payment.smart2pay.methods.countries[country]
                )
                {
                    console.log( 'Get new methods for [' + country + ']' );

                    var items_arr = [];

                    for( var method_id in window.checkoutConfig.payment.smart2pay.methods.countries[country] )
                    {
                        if( !window.checkoutConfig.payment.smart2pay.methods.methods[method_id] )
                            continue;

                        var item_obj = {
                            main_renderer: this,
                            id: method_id,
                            title: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].display_name,
                            description: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].description,
                            logo_url: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].logo_url,
                            fixed_amount: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].fixed_amount,
                            surcharge: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].surcharge
                        };

                        item_obj.myself = item_obj;

                        items_arr.push( item_obj );
                    }

                    if( items_arr.length == 1 )
                        this.selectedS2PPaymentMethod( items_arr[0].id );

                    methodsList( items_arr );
                }
            },

            testFunction: function ()
            {
                return 'This is from main renderer.';
            },

            getInstructions: function ()
            {
                return '';
            },

            getS2PMethods: function()
            {
                return methodsList();
            }
        });
    }
);
