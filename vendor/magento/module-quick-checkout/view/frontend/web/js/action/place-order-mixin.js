/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Checkout/js/model/quote',
    'Magento_QuickCheckout/js/model/event-tracking',
    'mage/utils/wrapper'
], function (quote, eventTracking, wrapper) {
    'use strict';
    return function (placeOrderAction) {
        /** Override default place order action and clean up bolt data in billing address  */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            var address = quote.billingAddress();

            eventTracking.sendClickPayButtonEvent();

            if (address !== null
                && typeof address !== 'undefined'
                && address.extensionAttributes !== null
                && typeof address.extensionAttributes !== 'undefined'
            ) {
                delete quote.billingAddress().extensionAttributes.boltId;
                if (typeof quote.billingAddress().extensionAttributes === 'object') {
                    quote.billingAddress().extensionAttributes = undefined;
                }
            }
            return originalAction(paymentData, messageContainer).done(
                function (result) {
                    if (result) {
                        eventTracking.sendPaymentCompleteEvent();
                    }
                }).fail(
                    function () {
                        eventTracking.sendPaymentRejectedEvent();
                    }
                );
        });
    };
});
