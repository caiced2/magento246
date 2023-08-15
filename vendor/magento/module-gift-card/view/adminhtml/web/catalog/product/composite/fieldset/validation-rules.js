/**
 * GiftCard client side validation rules
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global productConfigure */
/* eslint-disable strict */
define([
    'jquery',
    'mage/validation',
    'Magento_Catalog/catalog/product/composite/configure'
], function ($) {
    $.validator.addMethod('giftcard-min-amount', function (v) {
        return productConfigure.giftcardConfig.parsePrice(v) >= productConfigure.giftcardConfig.minAllowedAmount;
    }, 'The amount you entered is too low.');

    $.validator.addMethod('giftcard-max-amount', function (v) {
        if (productConfigure.giftcardConfig.maxAllowedAmount === 0) {
            return true;
        }

        return productConfigure.giftcardConfig.parsePrice(v) <= productConfigure.giftcardConfig.maxAllowedAmount;
    }, 'The amount you entered is too high.');
});
