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
     * Dispatch checkout events to GA
     *
     * @param {Object} cart - cart data
     * @param {String} stepIndex - step index
     * @param {String} stepDescription - step description
     *
     * @private
     */
    function notify(cart, stepIndex, stepDescription) {
        var i = 0,
            product,
            dlUpdate = {
                event: 'checkout',
                ecommerce: {
                    'currencyCode': window.dlCurrencyCode,
                    'checkout': {
                        'actionField': {
                            'step': stepIndex,
                            'description': stepDescription
                        },
                        products: []
                    }
                }
            };

        for (i; i < cart.length; i++) {
            product = cart[i];
            dlUpdate.ecommerce.checkout.products.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: product.qty
            });
        }

        window.dataLayer.push(dlUpdate);
        window.dataLayer.push({
            ecommerce: {
                checkout: 0
            }
        });
    }

    return function (data) {
        var events = {
                login: {
                    desctiption: 'login',
                    index: '1'
                },
                addresses: {
                    desctiption: 'addresses',
                    index: '2'
                },
                multishipping: {
                    desctiption: 'multishipping',
                    index: '3'
                },
                multibilling: {
                    desctiption: 'multibilling',
                    index: '4'
                },
                multireview: {
                    desctiption: 'multireview',
                    index: '5'
                }
            };

        window.dataLayer ?
            notify(data.cart, events[data.step].index, events[data.step].desctiption) :
            $(document).on(
                'ga:inited',
                notify.bind(this, data.cart, events[data.step].index, events[data.step].desctiption)
            );
    };
});
