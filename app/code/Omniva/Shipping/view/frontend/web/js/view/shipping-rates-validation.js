/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Omniva_Shipping/js/model/shipping-rates-validator',
        'Omniva_Shipping/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        omnivaShippingRatesValidator,
        omnivaShippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('omniva', omnivaShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('omniva', omnivaShippingRatesValidationRules);
        return Component;
    }
);
