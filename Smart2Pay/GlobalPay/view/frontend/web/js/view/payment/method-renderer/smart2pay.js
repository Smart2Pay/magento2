define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Smart2Pay_GlobalPay/js/model/payment/method-list',
        'Smart2Pay_GlobalPay/js/checkout-data',
        'Magento_Checkout/js/action/place-order',
        'uiLayout',
        'Magento_Ui/js/model/messages',
        'mage/translate'
    ],
    function (ko, $, Component, selectPaymentMethodAction, additionalValidators, quote, checkoutData, urlBuilder, url, methodsList, s2pCheckoutData, placeOrderAction, layout, Messages) {
        'use strict';

        var messageComponents;

        return Component.extend({
            self: this,
            defaults: {
                template: 'Smart2Pay_GlobalPay/payment/smart2pay',
                selectedS2PPaymentMethod: 0, //s2pCheckoutData.getSelectedS2PMethod(),
                selectedS2PCountry: '',
                isS2PPlaceOrderActionAllowed: false,
                requestHasError: false,
                requestErrorMessage: "",
                addressSubscription: null
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'selectedS2PPaymentMethod',
                        'requestHasError',
                        'requestErrorMessage',
                        'isS2PPlaceOrderActionAllowed'
                    ]);

                this.addressSubscription = quote.billingAddress.subscribe(function () {
                    this.updateCurrentCountry();
                }, this);

                return this;
            },

            initialize: function () {
                var self = this;
                this._super();

                var messageComponents = {};
                var messageContainer = new Messages();
                var name = 'messages-smart2pay';
                var messagesComponent = {
                    parent: self.name,
                    name: name,
                    displayArea: name,
                    component: 'Magento_Ui/js/view/messages',
                    config: {
                        messageContainer: messageContainer
                    }
                };

                layout([messagesComponent]);

                messageComponents[name] = messageContainer;

                self.messageComponents = messageComponents;
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
                // window.location.replace( url.build( 'smart2pay/payment/send' ) );

                $.mage.redirect(
                    url.build( 'smart2pay/payment/send' )
                );
            },

            // END Overwrite properties / functions

            selectS2PPaymentMethod: function( method )
            {
                var self = this;

                checkoutData.setSelectedPaymentMethod( self.item.method );
                //this.selectedS2PPaymentMethod( method );
                self.selectedS2PPaymentMethod = method;

                // s2pCheckoutData.setSelectedS2PMethod( method );

                var allow_order = false;
                if( method != 0 )
                    allow_order = (this.selectedS2PCountry != '');

                //this.selectPaymentMethod();

                this.isS2PPlaceOrderActionAllowed( allow_order );
                selectPaymentMethodAction( this.getData() );
            },

            getData: function() {

                var self = this;

                return {
                    "method": this.item.method,
                    "additional_data": {
                        "sp_method": self.selectedS2PPaymentMethod, // s2pCheckoutData.getSelectedS2PMethod(),
                        "selected_country": self.selectedS2PCountry
                    }
                };
            },

            updateCurrentCountry: function()
            {
                var new_country = '';
                if( quote.billingAddress() && quote.billingAddress().countryId )
                    new_country = quote.billingAddress().countryId;

                if( (new_country == '' && this.isS2PPlaceOrderActionAllowed())
                // || !this.selectedS2PPaymentMethod() )
                 || !this.selectedS2PPaymentMethod )
                    this.isS2PPlaceOrderActionAllowed( false );

                if( this.selectedS2PCountry != new_country )
                {
                    this.selectedS2PCountry = new_country;
                    this.refreshMethods( new_country );
                }
            },

            placeS2POrder: function()
            {
                var self = this;

                if( !this.validate()
                 || !additionalValidators.validate() )
                    return false;

                self.requestErrorMessage( "" );
                self.requestHasError( false );
                self.isPlaceOrderActionAllowed( false );

                var messageContainer = self.messageComponents['messages-smart2pay'];

                $.when(
                    placeOrderAction( this.getData(), messageContainer )
                ).fail(
                    function ( jqXHR, textStatus, errorThrown ) {

                        console.log( 'FAILED placing' );
                        console.log( errorThrown );
                        console.log( textStatus );
                        console.log( jqXHR );
                        console.log( 'OK' );

                        var error_msg = $.mage.__( "An error occured while placing the order. Please try again." );
                        if( typeof jqXHR.responseJSON !== "undefined"
                         && typeof jqXHR.responseJSON.message !== "undefined" )
                            error_msg = jqXHR.responseJSON.message;

                        self.requestErrorMessage( error_msg );
                        self.requestHasError( true );

                        console.log( 'ERRORMSG' );
                        console.log( error_msg );
                        console.log( 'OK' );

                        self.isPlaceOrderActionAllowed( true );
                    }
                ).done(
                    function ( data, textStatus, jqXHR ) {

                        console.log( 'Done placing' );
                        console.log( data );
                        console.log( textStatus );
                        console.log( jqXHR );
                        console.log( 'OK' );

                        self.afterPlaceOrder();

                        //$.mage.redirect(
                        //    window.checkoutConfig.payment[quote.paymentMethod().method].redirectUrl
                        //);
                    }
                );
            },

            refreshMethods: function( country )
            {
                var self = this;

                methodsList([]);

                // this.isS2PPlaceOrderActionAllowed( false );

                if( country
                 && typeof window.checkoutConfig.payment.smart2pay.methods != "undefined"
                 && window.checkoutConfig.payment.smart2pay.methods
                 && typeof window.checkoutConfig.payment.smart2pay.methods.countries != "undefined"
                 && window.checkoutConfig.payment.smart2pay.methods.countries
                 && window.checkoutConfig.payment.smart2pay.methods.countries[country]
                )
                {
                    var items_arr = [];

                    for( var method_id in window.checkoutConfig.payment.smart2pay.methods.countries[country] )
                    {
                        if( !window.checkoutConfig.payment.smart2pay.methods.methods[method_id] )
                            continue;

                        var item_obj = {};

                        item_obj = {
                            main_renderer: self,
                            id: method_id,
                            title: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].display_name,
                            description: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].description,
                            logo_url: window.checkoutConfig.payment.smart2pay.methods.methods[method_id].logo_url,
                            fixed_amount: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].fixed_amount,
                            surcharge: window.checkoutConfig.payment.smart2pay.methods.countries[country][method_id].surcharge,
                            myself: null,

                            selectMe: function()
                            {
                                self.selectS2PPaymentMethod( this.id );
                                return true;
                            },

                            isMethodChecked: ko.computed(function () {
                                //console.log( '[' + method_id + '] vs [' + s2pCheckoutData.getSelectedS2PMethod() + ']' );
                                //console.log( '[' + (method_id == s2pCheckoutData.getSelectedS2PMethod()) + ']' );
                                return ((method_id == s2pCheckoutData.getSelectedS2PMethod())?'checked':null);
                            }, item_obj)

                            /*
                            isMethodChecked: function()
                            {
                                return (this.id == s2pCheckoutData.getSelectedS2PMethod());
                            }
                            */
                        };

                        item_obj.myself = item_obj;

                        items_arr.push( item_obj );
                    }

                    //if( items_arr.length == 1 )
                    //    this.selectS2PPaymentMethod( items_arr[0].id );

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
