var config = {
    /*
    "map": {
        "*": {
            "Magento_Checkout/js/model/shipping-save-processor/default" : "Omniva_Shipping/js/shipping-save-processor-default-override",
        }
    },
     * 
     */
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'Omniva_Shipping/js/action/mixin/set-shipping-information-mixin': true
            }
        }
    },
    paths: {
        leaflet: 'https://unpkg.com/leaflet@1.6.0/dist/leaflet',
        leafletmarkercluster: 'https://unpkg.com/leaflet.markercluster@1.5.1/dist/leaflet.markercluster'
    },
    shim: {
        leaflet: {
            exports: 'L'
        },
        leafletmarkercluster: {
            deps: ['leaflet']
        }
    }
};