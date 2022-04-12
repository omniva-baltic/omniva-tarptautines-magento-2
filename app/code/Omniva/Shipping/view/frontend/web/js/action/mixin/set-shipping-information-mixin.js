define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Omniva_Shipping/js/omniva-global-data'
], function($, wrapper, quote, globalMessageList, $t, $omnivaData) {
    'use strict';

    return function(shippingInformationAction) {

        return wrapper.wrap(
            shippingInformationAction,
            function(originalAction) {
                let selectedShippingMethod = quote.shippingMethod();
                let shippingAddress = quote.shippingAddress();
                
                
                if (selectedShippingMethod.carrier_code !== 'omniva') {
                    return originalAction();
                }
                
                let terminal = $omnivaData.getPickupPoint();
                
                if (selectedShippingMethod.method_code === 'PARCEL_TERMINAL' &&
                    !terminal) {
                    globalMessageList.addErrorMessage(
                        {message: $t('Select Omniva parcel terminal!')});
                    jQuery(window).scrollTop(0);
                    return originalAction();
                }
                
                if (shippingAddress.extensionAttributes === undefined) {
                    shippingAddress.extensionAttributes = {};
                }
                
                shippingAddress.extensionAttributes.omniva_int_terminal = terminal;

                return originalAction();
            });
    };
});
