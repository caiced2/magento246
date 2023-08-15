/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/* jscs:disable */
/* eslint-disable */
define([
    'jquery'
], function ($) {
    'use strict';

    function init(config) {
        var f, j, dl;
        window.dataLayer = window.dataLayer || [];

        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            f = d.getElementsByTagName(s)[0];
            j = d.createElement(s);
            dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', config.gtmAccountId);
        dataLayer.push({
            'ecommerce': {'currencyCode': config.storeCurrencyCode}
        });
        if (config.refundJson.hasOwnProperty('event')) {
            console.log(config.refundJson);
            dataLayer.push(config.refundJson);
        }
    }

    /**
     * @param {Object} config
     */
    return function (config) {
        init(config);
    }
});
