/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_QuickCheckout/js/model/account-handling',
    'Magento_QuickCheckout/js/model/can-navigate-to-payment'
], function (
    setShippingInfoAction,
    stepNavigator,
    accountHandling,
    canNavigateToPayment
) {
    'use strict';

    return function () {
        if (canNavigateToPayment()) {
            if (window.location.href.indexOf('#payment') === -1) {
                stepNavigator.next();
            }
        }
        setShippingInfoAction().fail(
            function () {
                stepNavigator.navigateTo('shipping');
            }
        ).always(
            function () {
                accountHandling.setBoltUser(true);
            }
        );
    };
});
