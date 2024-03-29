define(
        ['mage/translate',
            'Magento_Ui/js/model/messageList',
            'Magento_Checkout/js/model/quote',
            'Omniva_Shipping/js/omniva-global-data'],
        function ($t, messageList, quote, $omnivaData) {
            'use strict';
            return {
                validate: function () {
                    var isValid = true;

                    let selectedShippingMethod = quote.shippingMethod();

                    if (selectedShippingMethod !== null && selectedShippingMethod.carrier_code === 'omniva') {
                        let terminal = $omnivaData.getPickupPoint();
                        if (selectedShippingMethod.method_code === 'PARCEL_TERMINAL' && !terminal) {
                            messageList.addErrorMessage({message: $t('Select parcel terminal')});
                            isValid = false;
                        }
                    }

                    return isValid;
                }
            }
        }
);
