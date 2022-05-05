define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-service',
    'Omniva_Shipping/js/view/checkout/shipping/parcel-terminal-service',
    'mage/translate',
    'Omniva_Shipping/js/omniva-global-data',
    'leafletmarkercluster',
    'Omniva_Shipping/js/terminal',
    'Omniva_Shipping/js/omniva'
], function ($, ko, Component, quote, shippingService, parcelTerminalService, t, omnivaData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Omniva_Shipping/checkout/shipping/form'
        },

        initialize: function (config) {
            this.parcelTerminals = ko.observableArray();
            this.selectedParcelTerminal = ko.observable();
            this._super();
        },
        hideSelect: function () {
            var method = quote.shippingMethod();
            var selectedMethod = method !== null ? method.method_code + '_' + method.carrier_code : null;
            if (selectedMethod && selectedMethod.includes('_terminal_omnivaglobal')) {
                if ($('#omniva_global_map_container .tmjs-container').length === 0) {
                    var that = this;
                    setTimeout(function() {
                        that.createMap(method);
                      }, 500);
                    
                } else {
                    $('#omniva_global_map_container').first().show();
                }
            } else {
                $('#omniva_global_map_container').first().hide();
            }
        },
        createMap: function (method) {
            this.setData(method);
            if ($('#omniva_global_map_container').length === 0) {
                if ($('#s_method_' + method.carrier_code + '_' + method.method_code).length > 0) {
                    var move_after = $('#s_method_' + method.carrier_code + '_' + method.method_code).parents('tr');
                } else if ($('#label_method_' + method.method_code + '_' + method.carrier_code).length > 0) {
                    var move_after = $('#label_method_' + method.method_code + '_' + method.carrier_code).parents('tr');
                }
                $('<tr id = "omniva_global_map_container" ><td colspan = "4" style = "border-top: none; padding-top: 0px"></td></tr>').insertAfter(move_after);
            }
            $('body').trigger('load-omniva-terminals');  
            $('input[name="omniva_global_terminal"]').on('change', function (){
                console.log('saving');
                omnivaData.setPickupPoint($(this).val());
            });
        },
        setData: function(method) {
            var address = quote.shippingAddress();
            var data = method.method_code.replace('_terminal', '').split('_');
            //unset service type
            data.splice(0, 1);
            var identifier = data.join('_');
            window.omnivaGlobalSettings = {
                max_distance: window.checkoutConfig.omnivaGlobalData.distance,
                identifier: identifier,
                country: address.countryId,
                api_url: window.checkoutConfig.omnivaGlobalData.apiUrl,    
                city: address.city,
                postcode: address.postcode,
                address: address.street[0]
            };
            omnivaGlobalData.text_select_terminal = $.mage.__('Select terminal');
            omnivaGlobalData.text_select_post = $.mage.__('Select post office', 'omniva_global');
            omnivaGlobalData.text_search_placeholder = $.mage.__('Enter postcode', 'omniva_global');
            omnivaGlobalData.text_not_found = $.mage.__('Place not found', 'omniva_global');
            omnivaGlobalData.text_enter_address = $.mage.__('Enter postcode/address', 'omniva_global');
            omnivaGlobalData.text_map = $.mage.__('Terminals map', 'omniva_global');
            omnivaGlobalData.text_list = $.mage.__('Terminals list', 'omniva_global');
            omnivaGlobalData.text_search = $.mage.__('Search', 'omniva_global');
            omnivaGlobalData.text_reset = $.mage.__('Reset search', 'omniva_global');
            omnivaGlobalData.text_select = $.mage.__('Select', 'omniva_global');
            omnivaGlobalData.text_no_city = $.mage.__('City not found', 'omniva_global');
            omnivaGlobalData.text_my_loc = $.mage.__('Use my location', 'omniva_global');
        },
        moveSelect: function () {
            $('#checkout-shipping-method-load input:radio:not(.omnivaglobalbound)').addClass('omnivaglobalbound').bind('click', this.hideSelect());
        },
        initObservable: function () {
            this._super();
            quote.shippingMethod.subscribe(function (method) {
                this.moveSelect();
                
            }, this);


            return this;
        },

        setParcelTerminalList: function (list) {
            this.parcelTerminals(list);
            this.moveSelect();
        },

        reloadParcelTerminals: function () {
            parcelTerminalService.getParcelTerminalList(quote.shippingAddress(), this, 1);
            this.moveSelect();
        },

        getParcelTerminal: function () {
            var parcelTerminal;
            if (this.selectedParcelTerminal()) {
                for (var i in this.parcelTerminals()) {
                    var m = this.parcelTerminals()[i];
                    if (m.name == this.selectedParcelTerminal()) {
                        parcelTerminal = m;
                    }
                }
            } else {
                parcelTerminal = this.parcelTerminals()[0];
            }

            return parcelTerminal;
        },

        initSelector: function () {
            var startParcelTerminal = this.getParcelTerminal();
        }
    });
});