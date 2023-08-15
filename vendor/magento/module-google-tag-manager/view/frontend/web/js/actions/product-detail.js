/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_GoogleTagManager/js/google-tag-manager'
], function ($) {
    'use strict';

    /**
     * Dispatch product detail event to GA
     *
     * @param {Object} data - product data
     *
     * @private
     */
    function notify(data) {
        window.dataLayer.push({
            'event': 'productDetail',
            'ecommerce': {
                'detail': {
                    'products': [data]
                },
                'impressions': []
            }
        });
    }

    return function (productData) {
        window.dataLayer ?
            notify(productData) :
            $(document).on('ga:inited', notify.bind(this, productData));
    };
});
