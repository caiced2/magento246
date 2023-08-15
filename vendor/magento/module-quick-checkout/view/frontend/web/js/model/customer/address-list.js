/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_QuickCheckout/js/model/customer/customer-addresses'
], function (ko, defaultProvider) {
    'use strict';
    return ko.observableArray(defaultProvider.getAddressItems());
});
