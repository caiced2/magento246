/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
        'jquery',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader'
    ], function (
        $,
        storage,
        fullScreenLoader
    ) {
        'use strict';

        return function (code) {
            var payload = {code: code},
                refreshResult = $.Deferred();

            fullScreenLoader.startLoader();

            storage.post(
                'quick-checkout/ajax/refresh',
                JSON.stringify(payload),
                false
            ).done(function (response) {
                refreshResult.resolve(response.success || false);
            }).fail(function () {
                refreshResult.resolve(false);
            }).always(function () {
                fullScreenLoader.stopLoader();
            });
            return refreshResult;
        };
    }
);
