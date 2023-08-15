/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/

define([
    'jquery',
    'uiComponent'
], function ($, Class) {
    'use strict';

    return Class.extend({
        defaults: {
            $container: null
        },

        /**
         * Initialization
         */
        initialize: function (config, element) {
            this._super();
            this.$container = $(element);
            this.initEventHandlers();

            return this;
        },

        /**
         * Updated order totals section
         */
        updateTotals: function (event) {
            var data = {};

            data['payment[use_customer_balance]'] = event.currentTarget.checked ? 1 : 0;
            window.order.loadArea(['totals', 'billing_method'], true, data);
        },

        /**
         * Init event handlers
         */
        initEventHandlers: function () {
            this.$container.on('change', this.updateTotals.bind(this));
        }
    });
});
