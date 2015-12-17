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

            isPlaceOrderActionAllowed: ko.observable(false),

            // END Overwrite properties / functions

            selectedS2PPaymentMethod: ko.observable(null),
            selectedS2PCountry: '',

            isPaymentMethodSelected: ko.observable(false),

            //    console.log( "selfComponent: " );
            //    console.log( selfComponent );
            //    for (var i in selfComponent){
            //        console.log(i);
            //        console.log(selfComponent[i]);
            //
            //    }
            //    console.log( selfComponent.isPaymentMethodSelected );
            //    return quote.billingAddress() != null && selfComponent.isPaymentMethodSelected();
            //}),

            s2pSelectPaymentMethod: function( method )
            {
                this.selectedS2PPaymentMethod(method);

                if( method == 0 )
                {
                    this.isPaymentMethodSelected( false );
                    this.isPlaceOrderActionAllowed( false );
                } else
                {
                    this.isPaymentMethodSelected( true );
                    this.isPlaceOrderActionAllowed( quote.billingAddress() != null );
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
                    "additional_data": null,
                    "s2p_payment_method": self.selectedS2PPaymentMethod()
                };
            },

            updateCurrentCountry: function()
            {
                var new_country = '';
                if( quote.billingAddress() && quote.billingAddress().countryId )
                    new_country = quote.billingAddress().countryId;

                console.log( 'Check country [' + this.selectedS2PCountry + '] != [' + new_country + ']' );

                if( this.selectedS2PCountry != new_country )
                {
                    console.log( 'NEW country [' + new_country + ']' );
                    this.selectedS2PCountry = new_country;
                    this.refreshMethods( new_country );
                }
            },

            getSmart2PayData: function ()
            {
                var self = this;

                this.isPlaceOrderActionAllowed( false );

                quote.billingAddress.subscribe(function () {
                    self.updateCurrentCountry();
                });

                this.updateCurrentCountry();

                return '';
            },

            refreshMethods: function( country )
            {
                methodsList([]);

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

                        //console.log( method_id );
                        //console.log( window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].fixed_amount );
                        //console.log( window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].surcharge );
                        //console.log( window.checkoutConfig.payment.smart2pay.methods.methods[method_id].display_name );
                        //console.log( window.checkoutConfig.payment.smart2pay.methods.methods[method_id].logo_url );
                        //console.log( window.checkoutConfig.payment.smart2pay.methods.methods[method_id].description );

                        var item_obj = {
                            id: method_id,
                            title: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].display_name,
                            description: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].description,
                            logo_url: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].logo_url,
                            fixed_amount: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].fixed_amount,
                            surcharge: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].surcharge,
                            main_renderer: this
                        };

                        items_arr.push( item_obj );
                    }

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
                return methodsList;
            }
        });
    }
);
