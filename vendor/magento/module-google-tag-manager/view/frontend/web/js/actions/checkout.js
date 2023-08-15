/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Checkout/js/view/payment',
    'Magento_GoogleTagManager/js/google-tag-manager'
], function ($, payment) {
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
                'event': 'checkout',
                'ecommerce': {
                    'currencyCode': window.dlCurrencyCode,
                    'checkout': {
                        'actionField': {
                            'step': stepIndex,
                            'description': stepDescription
                        },
                        'products': [ ]
                    }
                }
            };

        for (i; i < cart.length; i++) {
            product = cart[i];
            dlUpdate.ecommerce.checkout.products.push({
                'id': product.id,
                'name': product.name,
                'price': product.price,
                'quantity': product.qty
            });
        }

        window.dataLayer.push(dlUpdate);
    }

    return function (data) {
        var events = {
                shipping: {
                    desctiption: 'shipping',
                    index: '1'
                },
                payment: {
                    desctiption: 'payment',
                    index: '2'
                }
            },
            subscription = payment.prototype.isVisible.subscribe(function (value) {
                if (value && window.dataLayer) {
                    notify(data.cart, events.payment.index, events.payment.desctiption);
                    subscription.dispose();
                }
            });

        window.dataLayer ?
            notify(data.cart, events.shipping.index, events.shipping.desctiption) :
            $(document).on(
                'ga:inited',
                notify.bind(this, data.cart, events.shipping.index, events.shipping.desctiption)
            );
    };
});

