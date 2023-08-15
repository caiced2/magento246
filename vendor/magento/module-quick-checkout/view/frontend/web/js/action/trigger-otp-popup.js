/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_QuickCheckout/js/model/bolt-embed',
    'Magento_QuickCheckout/js/action/otp-login'
], function ($, boltEmbed, otpLogin) {
    'use strict';

    return function (email, selector) {
        var fieldSelector = selector || '#customer-email-fieldset',
            authorizationCode = '',
            authComponent = boltEmbed.createAuthComponent();

        if ($(fieldSelector).is(':visible') === false) {
            return;
        }

        authComponent.mount(fieldSelector).then(function () {
            authComponent.authorize({ email: email }).then(function (data) {
                authComponent.unmount();
                if (typeof data !== 'undefined') {
                    authorizationCode = data.authorizationCode;
                    otpLogin(authorizationCode);
                }
            });
        });
    };
});
