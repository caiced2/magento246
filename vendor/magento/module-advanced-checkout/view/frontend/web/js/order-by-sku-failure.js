/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget',
    'jquery/validate'
], function ($) {
    'use strict';

    /**
     * This widget handles Order By Sku Failure rendering.
     */
    $.widget('mage.orderBySkuFailure', {
        options: {
            qtyInputSelector: '[data-role="input-qty"]',
            skuFailedQtySelector: '[data-role="sku-failed-qty"]'
        },

        /**
         * This method binds elements found in this widget.
         * @private
         */
        _bind: function () {
            var handlers = {};

            // bind quantity input change
            handlers['keyup ' + this.options.qtyInputSelector] = '_qtyChange';
            handlers['input ' + this.options.qtyInputSelector] = '_qtyChange';

            this._on(handlers);
        },

        /**
         * This method constructs a new widget.
         * @private
         */
        _create: function () {
            this._bind();

            // trigger the validation since it has been pre-validated on the server
            this._getQtyInput().valid();
        },

        /**
         * This method handles a change in quantity.
         * @private
         */
        _qtyChange: function () {
            var qtyInput = this._getQtyInput();

            // validate
            qtyInput.valid();
            // update hidden quantity element
            this._updateItemQty(qtyInput);
        },

        /**
         * This method handles update of item quantity.
         * @private
         */
        _updateItemQty: function (qtyInput) {
            // update hidden sku failed quantity element with changed quantity
            this.element.find(this.options.skuFailedQtySelector).val(qtyInput.val());
        },

        /**
         * This method returns the quantity input element for this item.
         * @private
         */
        _getQtyInput: function () {
            return this.element.find(this.options.qtyInputSelector);
        }
    });

    // override validator error messages, as errors displayed are currently included on page from server.
    $.extend($.validator.messages, {
        'required': '',
        'validate-greater-than-zero': ''
    });

    return $.mage.orderBySkuFailure;
});
