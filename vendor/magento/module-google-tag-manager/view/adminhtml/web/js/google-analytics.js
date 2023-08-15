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
        (function (d,s,u) {
            var gtagScript = d.createElement(s);
            gtagScript.type = 'text/javascript';
            gtagScript.async = true;
            gtagScript.src = u;
            d.head.insertBefore(gtagScript, d.head.children[0]);
        })(document, 'script', 'https://www.googletagmanager.com/gtag/js?id=' + config.measurementId);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('set', 'developer_id.dYjhlMD', true);
        gtag('config', config.measurementId, { 'anonymize_ip': true });
        dataLayer.push({
            'ecommerce': {'currencyCode': config.storeCurrencyCode}
        });
        if (config.refundJson.hasOwnProperty('event')) {
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
