/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/checkout-data',
    'Magento_QuickCheckout/js/model/account-handling'
], function ($, wrapper, checkoutData, bolt) {
    'use strict';

    return function (checkoutDataResolver) {
        checkoutDataResolver.applyShippingAddress = wrapper.wrap(
            checkoutDataResolver.applyShippingAddress, function (originalAction) {
                if (typeof window.checkoutConfig.payment.quick_checkout.canUseSso !== 'undefined'
                    && window.checkoutConfig.payment.quick_checkout.canUseSso === true
                ) {
                    return;
                }
                originalAction();
            });

        checkoutDataResolver.resolveBillingAddress = wrapper.wrap(
            checkoutDataResolver.resolveBillingAddress, function (originalAction) {
                var selectedBillingAddress = null,
                    boltBillingAddressId = null,
                    cards = null;

                if (bolt.isBoltUser()) {
                    selectedBillingAddress = checkoutData.getSelectedBillingAddress();
                    cards = bolt.getBoltCards();
                    if (cards.length > 0 && selectedBillingAddress) {
                        cards.some(function (data) {
                            boltBillingAddressId = data.billing_address.extensionAttributes.boltId;
                            if (selectedBillingAddress === 'bolt-customer-address' + boltBillingAddressId) {
                                bolt.assignAddressData(data.billing_address, 'billing');
                            }
                        });
                    }
                }
                return originalAction();
            });

        return checkoutDataResolver;
    };
});
