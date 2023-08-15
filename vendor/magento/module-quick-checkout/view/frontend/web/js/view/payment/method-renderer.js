/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'quick_checkout',
        component: 'Magento_QuickCheckout/js/view/payment/method-renderer/credit-card'
    });

    return Component.extend({});
});
