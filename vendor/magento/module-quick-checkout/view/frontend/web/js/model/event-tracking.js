/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_QuickCheckout/js/model/account-handling'
], function (
    quote,
    customer,
    bolt
) {
    'use strict';

    return {
        defaults: {},

        /**
         * @returns {boolean}
         */
        isBoltTrackingCheckoutEnabled: function () {
            return window.checkoutConfig.payment.quick_checkout.canTrackCheckout === true;
        },

        getShopperType: function () {
            if (bolt.isBoltUser()) {
                return 'bolt';
            }

            if (customer.isLoggedIn()) {
                return 'merchant';
            }

            return 'guest';
        },

        /**
         * @param {string} eventName
         * @param {Object} additionalEventInfo
         * @returns {boolean}
         */
        sendTrackingEvent: function (eventName, additionalEventInfo = {}) {
            if (bolt.isBoltCheckoutEnabled() && this.isBoltTrackingCheckoutEnabled() && window.BoltAnalytics) {
                additionalEventInfo.cartId = quote.getQuoteId();
                additionalEventInfo.shopperType = this.getShopperType();
                window.BoltAnalytics.checkoutStepComplete(eventName, additionalEventInfo);
            }
        },

        /**
         * @param {boolean} hasBoltAccount
         */
        sendAccountRecognitionEvent: function (hasBoltAccount) {
            var additionalEventInfo = {
                hasBoltAccount: Boolean(hasBoltAccount),
                detectionMethod: 'email'
            };

            this.sendTrackingEvent('Account recognition check performed', additionalEventInfo);
        },

        /**
         * Notify about a user that exited the checkout session
         */
        sendExitEvent: function () {
            this.sendTrackingEvent('Exit');
        },

        sendDetailEntryBeganEvent: function () {
            this.sendTrackingEvent('Detail entry began');
        },

        sendShippingMethodStepCompleteEvent: function () {
            this.sendTrackingEvent('Shipping method step complete');
        },

        sendPaymentMethodSelectedEvent: function (paymentMethod) {
            var additionalEventInfo = { paymentOption: paymentMethod.method };

            this.sendTrackingEvent('Payment method selected', additionalEventInfo);
        },

        sendClickPayButtonEvent: function () {
            this.sendTrackingEvent('Payment details fully entered');
        },

        sendPaymentCompleteEvent: function () {
            this.sendTrackingEvent('Payment complete');
        },

        sendPaymentRejectedEvent: function () {
            this.sendTrackingEvent('Payment rejected');
        }
    };
});
