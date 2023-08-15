/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'jquery'
], function (Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
            failedItems: '[data-role="message-notice"]',
            skuAttentionNum: '#sku-attention-num',
            actionPrimary: '[data-role="action"]',
            errorMessage: '[data-role="error-title"]'
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            $(this.actionPrimary).on('click', this.updateErrorMessage.bind(this));
        },

        /**
         * Updates count of errors or remove the error message
         */
        updateErrorMessage: function () {
            var length = $(this.failedItems).length;

            if (length) {
                $(this.skuAttentionNum).html(length);
            } else {
                $(this.errorMessage).remove();
            }
        }
    });
});
