/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/checkout-data'
], function (ko, checkoutData) {
    'use strict';

    var isBoltUser = ko.observable(
        checkoutData.getBoltAccountDetails() !== null && typeof checkoutData.getBoltAccountDetails() !== 'undefined'
    ),
        hasBoltAccount = ko.observable(checkoutData.getHasBoltAccount()).extend({ notify: 'always' }),
        isLoggedIn = ko.observable(window.checkoutConfig.payment.quick_checkout.isLoggedInBolt),
        hasWriteAccess = ko.observable(window.checkoutConfig.payment.quick_checkout.hasWriteAccess);

    return {
        isLoggedIn: isLoggedIn,
        isBoltUser: isBoltUser,
        hasBoltAccount: hasBoltAccount,
        hasWriteAccess: hasWriteAccess,

        /**
         * @param {Boolean} flag
         */
        setIsLoggedIn: function (flag) {
            isBoltUser(flag);
            if (flag) {
                this.setHasBoltAccount(flag);
            }
        },

        /**
         * @param {Boolean} flag
         */
        setHasBoltAccount: function (flag) {
            hasBoltAccount(flag);
            checkoutData.setHasBoltAccount(flag);
        },

        /**
         * @param {Boolean} flag
         */
        setHasWriteAccess: function (flag) {
            this.hasWriteAccess(flag);
        },

        /**
         * @returns {Boolean}
         */
        getHasWriteAccess: function () {
            return hasWriteAccess();
        },

        /**
         * Checks is the customer has the Bolt account info
         *
         * @returns {Boolean}
         */
        hasAccountInformation: function () {
            return checkoutData.getAccountInformationLoaded();
        },

        /**
         * Returns the customer email
         *
         * @returns {String}
         */
        getEmail: function () {
            var boltAccountDetails = checkoutData.getBoltAccountDetails();

            if (!boltAccountDetails || !boltAccountDetails.email) {
                return '';
            }

            return boltAccountDetails.email;
        }
    };
});
