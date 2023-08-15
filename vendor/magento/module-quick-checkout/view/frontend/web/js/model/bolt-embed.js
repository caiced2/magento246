/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([], function () {
    'use strict';

    var locale = window.checkoutConfig.payment['quick_checkout'].locale,
        publishableKey = window.checkoutConfig.payment['quick_checkout'].publishableKey,
        config = {
            language: locale,
            style: {
                position: 'right'
            }
        },
        boltEmbedded = window.Bolt(publishableKey);

    return {
        /**
         * Creates an authorization component
         * @returns {Object}
         */
        createAuthComponent: function () {
            return boltEmbedded.create('authorization_component', config);
        },

        /**
         * Logout from Bolt account
         * @return {Promise}
         */
        logout: function () {
            return boltEmbedded.helpers.logout();
        }
    };
});
