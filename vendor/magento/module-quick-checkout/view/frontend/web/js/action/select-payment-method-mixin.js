/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_QuickCheckout/js/model/event-tracking',
    'mage/utils/wrapper'
], function (eventTracking, wrapper) {
    'use strict';
    return function (selectPaymentMethodAction) {

        return wrapper.wrap(selectPaymentMethodAction, function (originalAction, paymentMethod) {

            if (paymentMethod) {
                eventTracking.sendPaymentMethodSelectedEvent(paymentMethod);
            }

            return originalAction(paymentMethod);
        });
    };
});
