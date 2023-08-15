/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/quote',
    'Magento_QuickCheckout/js/model/shipping-rate-processor/account-address',
    'Magento_Checkout/js/model/shipping-rate-registry'
], function (
    checkoutData,
    fullScreenLoader,
    shippingService,
    selectShippingMethodAction,
    quote,
    boltAddressProcessor,
    rateRegistry
) {
    'use strict';

    return function () {
        var cheapestRate = null,
            rates = [];

        return new Promise(function (resolve, reject) {
            rateRegistry.set(quote.shippingAddress().getCacheKey(), null);
            boltAddressProcessor.getRates(quote.shippingAddress()).done(function () {
                rates = shippingService.getShippingRates()();
                if (typeof rates === 'undefined' || rates.length <= 0) {
                    return false;
                }
                rates.reduce(function (prev, curr) {
                    cheapestRate = prev.base_amount < curr.base_amount ? prev : curr;
                    return cheapestRate;
                });
                if (!cheapestRate) {
                    // Set first rate as fallback
                    cheapestRate = rates[0];
                }
                selectShippingMethodAction(cheapestRate);
                checkoutData.setSelectedShippingRate(cheapestRate['carrier_code'] + '_' + cheapestRate['method_code']);
                resolve();
            }).fail(function () {
                reject();
            });
        });
    };
});
