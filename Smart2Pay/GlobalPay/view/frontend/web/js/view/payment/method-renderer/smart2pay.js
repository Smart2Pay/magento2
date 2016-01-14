define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Smart2Pay_GlobalPay/js/model/payment/method-list'
    ],
    function (ko, $, Component, quote, urlBuilder, url, methodsList) {
        'use strict';

        return Component.extend({

            defaults: {
                template: 'Smart2Pay_GlobalPay/payment/smart2pay',
                selectedS2PPaymentMethod: 0,
                selectedS2PCountry: '',
                isS2PPlaceOrderActionAllowed: false,
                addressSubscription: null,
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'selectedS2PPaymentMethod',
                        'isS2PPlaceOrderActionAllowed'
                    ]);

                this.addressSubscription = quote.billingAddress.subscribe(function () {
                    this.updateCurrentCountry();
                }, this);

                return this;
            },

            disposeSubscriptions: function () {
                if (this.addressSubscription) {
                    this.addressSubscription.dispose();
                }
            },

            // Overwrite properties / functions

            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function()
            {
                window.location.replace( url.build( 'smart2pay/payment/send/' ) );
            },

            // END Overwrite properties / functions

            selectS2PPaymentMethod: function( method, method_obj )
            {
                this.selectedS2PPaymentMethod( method );

                var allow_order = false;
                if( method != 0 )
                    allow_order = (this.selectedS2PCountry != '');

                this.selectPaymentMethod();

                this.isS2PPlaceOrderActionAllowed( allow_order );

                if( allow_order )
                    $('#s2p_place_order_button').prop('disabled', false );
            },

            getData: function() {
                return {
                    "method": this.item.method,
                    "po_number": null,
                    "additional_data": {
                        "sp_method": this.selectedS2PPaymentMethod(),
                        "selected_country": this.selectedS2PCountry
                    }
                };
            },

            updateCurrentCountry: function()
            {
                var new_country = '';
                if( quote.billingAddress() && quote.billingAddress().countryId )
                    new_country = quote.billingAddress().countryId;

                if( (new_country == '' && this.isS2PPlaceOrderActionAllowed())
                 || !this.selectedS2PPaymentMethod() )
                    this.isS2PPlaceOrderActionAllowed( false );

                if( this.selectedS2PCountry != new_country )
                {
                    this.selectedS2PCountry = new_country;
                    this.refreshMethods( new_country );
                }
            },

            refreshMethods: function( country )
            {
                var self = this;

                methodsList([]);

                // this.isS2PPlaceOrderActionAllowed( false );

                if( country
                 && window.checkoutConfig.payment.smart2pay.methods
                 && window.checkoutConfig.payment.smart2pay.methods.countries[country]
                )
                {
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
                            surcharge: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].surcharge,
                            myself: null,

                            selectMe: function()
                            {
                                self.selectS2PPaymentMethod( this.id, this.myself );
                                return true;
                            }
                        };

                        item_obj.myself = item_obj;

                        items_arr.push( item_obj );
                    }

                    if( items_arr.length == 1 )
                        this.selectedS2PPaymentMethod( items_arr[0].id );

                    methodsList( items_arr );
                }
            },

            getS2PMethods: function()
            {
                return methodsList();
            },

            displayTitle: function() {
                return ( window.checkoutConfig.payment.smart2pay.settings.display_mode == 'text'
                || window.checkoutConfig.payment.smart2pay.settings.display_mode == 'both');
            },

            displayLogo: function() {
                return ( window.checkoutConfig.payment.smart2pay.settings.display_mode == 'logo'
                || window.checkoutConfig.payment.smart2pay.settings.display_mode == 'both');
            },

            displayDescription: function() {
                return (window.checkoutConfig.payment.smart2pay.settings.display_description == 1);
            }
        });
    }
);
