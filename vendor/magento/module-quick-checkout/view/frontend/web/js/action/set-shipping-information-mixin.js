/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_QuickCheckout/js/model/event-tracking',
    'mage/utils/wrapper'
], function (eventTracking, wrapper) {
    'use strict';
    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            return originalAction().done(
                function () {
                    eventTracking.sendShippingMethodStepCompleteEvent();
                }
            );
        });
    };
});
