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
            if (selectedMethod && selectedMethod.includes('_terminal_omniva_global')) {
                if ($('#omniva_global_map_container .tmjs-container').length === 0) {
                    this.createMap(method);
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
        },
        moveSelect: function () {

            $('#checkout-shipping-method-load input:radio:not(.omnivaglobalbound)').addClass('omnivaglobalbound').bind('click', this.hideSelect());
            /*
             var omniva_last_selected_terminal = '';
             if ($('#omnivaglobal-terminal-select-location select').length > 0){
             omniva_last_selected_terminal = $('#omnivaglobal-terminal-select-location select').val();
             }
             if ($('#onepage-checkout-shipping-method-additional-load .parcel-terminal-list').length > 0){
             $('#checkout-shipping-method-load input:radio:not(.bound)').addClass('bound').bind('click', this.hideSelect());
             if ($('#checkout-shipping-method-load .parcel-terminal-list').html() !=  $('#onepage-checkout-shipping-method-additional-load .parcel-terminal-list').html()){
             $('#omnivaglobal-terminal-select-location').remove();
             }
             
             if ($('#checkout-shipping-method-load .parcel-terminal-list').length == 0){
             var terminal_list = $('#onepage-checkout-shipping-method-additional-load .omniva-parcel-terminal-list-wrapper div');
             var row = $.parseHTML('<tr><td colspan = "4" style = "border-top: none; padding-top: 0px"></td></tr>');
             if ($('#s_method_omniva_PARCEL_TERMINAL').length > 0){
             var move_after = $('#s_method_omniva_PARCEL_TERMINAL').parents('tr'); 
             } else if ($('#label_method_PARCEL_TERMINAL_omniva').length > 0){
             var move_after = $('#label_method_PARCEL_TERMINAL_omniva').parents('tr'); 
             }
             var cloned =  terminal_list.clone(true);
             if ($('#omnivaglobal-terminal-select-location').length == 0){
             $('<tr id = "omnivaglobal-omnivaglobal-terminal-select-location" ><td colspan = "4" style = "border-top: none; padding-top: 0px"></td></tr>').insertAfter(move_after);
             }
             cloned.appendTo($('#omnivaglobal-terminal-select-location td'));
             }
             }
             
             if($('#omnivaLtModal').length > 0 && $('.omniva-terminals-list').length == 0){
             if ($('#omnivaglobal-terminal-select-location select option').length>0){
             var omnivadata = [];
             omnivadata.omniva_plugin_url = require.toUrl('Omniva_Shipping/css/');
             omnivadata.omniva_current_country = quote.shippingAddress().countryId;
             omnivadata.text_select_terminal = $.mage.__('Select terminal');
             omnivadata.text_search_placeholder = $.mage.__('Enter postcode');
             omnivadata.not_found = $.mage.__('Place not found');
             omnivadata.text_enter_address = $.mage.__('Enter postcode / address');
             omnivadata.text_show_in_map = $.mage.__('Show in map');
             omnivadata.text_show_more = $.mage.__('Show more');
             omnivadata.postcode = quote.shippingAddress().postcode;
             $('#omnivaglobal-terminal-select-location select').omniva({omnivadata:omnivadata});
             }
             }
             if (typeof omniva_last_selected_terminal === 'undefined') {
             var omniva_last_selected_terminal = '';
             }
             if ($('#omnivaglobal-terminal-select-location select').val() != omniva_last_selected_terminal){
             $('#omnivaglobal-terminal-select-location select').val(omniva_last_selected_terminal);
             }
             $('#checkout-step-shipping_method').on('change', '#omnivaglobal-terminal-select-location select', function () {
             omnivaData.setPickupPoint($(this).val());
             });
             */
        },
        initObservable: function () {
            this._super();
            /*
             this.showParcelTerminalSelection = ko.computed(function() {
             this.moveSelect();
             return this.parcelTerminals().length != 0
             }, this);
             
             this.selectedMethod = ko.computed(function() {
             this.moveSelect();
             var method = quote.shippingMethod();
             var selectedMethod = method != null ? method.carrier_code + '_' + method.method_code : null;
             return selectedMethod;
             }, this);
             */
            quote.shippingMethod.subscribe(function (method) {
                this.moveSelect();
                //var selectedMethod = method != null ? method.carrier_code + '_' + method.method_code : null;
                //if (selectedMethod == 'omniva_PARCEL_TERMINAL') {
                //    this.reloadParcelTerminals();
                //}
            }, this);

            //this.selectedParcelTerminal.subscribe(function(terminal) {
            /*
             //not needed on one step checkout, is done from overide
             if (quote.shippingAddress().extensionAttributes == undefined) {
             quote.shippingAddress().extensionAttributes = {};
             }
             quote.shippingAddress().extensionAttributes.omniva_int_terminal = terminal;
             */
            //});

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