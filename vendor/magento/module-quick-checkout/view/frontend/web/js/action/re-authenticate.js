/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable max-nested-callbacks */
define([
        'jquery',
        'Magento_QuickCheckout/js/model/customer/customer',
        'Magento_QuickCheckout/js/model/bolt-embed',
        'Magento_QuickCheckout/js/action/otp-refresh'
    ], function (
        $,
        boltCustomer,
        boltEmbed,
        otpRefresh
    ) {
        'use strict';

        return function (selector) {
            var authComponent = boltEmbed.createAuthComponent(),
                authorizationCode = '',
                customerEmail = boltCustomer.getEmail(),
                reAuthResult = $.Deferred();

            authComponent.mount(selector).then(function () {
                authComponent.authorize({email: customerEmail}).then(function (authorizeResult) {
                    authComponent.unmount();
                    if (typeof authorizeResult === 'undefined') {
                        reAuthResult.resolve(false);
                        return;
                    }
                    authorizationCode = authorizeResult.authorizationCode;
                    $.when(otpRefresh(authorizationCode)).done(function (refreshResult) {
                        reAuthResult.resolve(refreshResult);
                    });
                });
            });
            return reAuthResult;
        };
    }
);
