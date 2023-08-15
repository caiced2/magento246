/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'mage/storage',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_QuickCheckout/js/model/event-tracking',
        'Magento_QuickCheckout/js/model/customer/customer',
        'Magento_QuickCheckout/js/action/trigger-otp-popup'
    ],
    function (storage, urlBuilder, fullScreenLoader, eventTracking, boltCustomer, triggerOtpPopupAction) {
        'use strict';

        return function (email) {
            fullScreenLoader.startLoader();

            return storage.post(
                urlBuilder.createUrl('/quick-checkout/has-account', {}),
                JSON.stringify({email: email}),
                false
            ).done(
                function (response) {
                    eventTracking.sendAccountRecognitionEvent(response);
                    if (response) {
                        triggerOtpPopupAction(email);
                    }
                    boltCustomer.setHasBoltAccount(response);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
