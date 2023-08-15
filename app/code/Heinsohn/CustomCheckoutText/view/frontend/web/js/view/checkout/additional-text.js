define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-registry'
], function ($, Component, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Heinsohn_CustomCheckoutText/checkout/additional-text'
        },

        isVisible: function () {
            var shippingAddress = quote.shippingAddress();
            return (shippingAddress && shippingAddress.countryId === 'CO');
        }
    });
});
