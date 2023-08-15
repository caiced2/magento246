/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'uiRegistry',
    'mageUtils',
    'Magento_Catalog/js/product/list/columns/final-price'
], function (_, registry, utils, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_GiftCard/product/price/minimal_price',
            showMinimalPrice: true
        },

        /**
         * Get gift card price amount.
         *
         * @param {Object} row
         * @return {Number} gift card price
         * @private
         */
        _getGiftCardPrice: function (row) {
            return row['price_info'];
        },

        /**
         * Check if minimal and maximum prices are equal.
         *
         * @param {Object} row
         * @return {Boolean}
         */
        isMinEqualToMax: function (row) {
            var min = this._getGiftCardPrice(row)['minimal_price'],
                max = this._getGiftCardPrice(row)['max_price'];

            return min === max;
        },

        /**
         * Check if minimal price exists.
         *
         * @param {Object} row
         * @return {Boolean}
         */
        hasMinValue: function (row) {
            return this._getGiftCardPrice(row)['minimal_price'] > 0;
        },

        /**
         * Get gift card minimal price.
         *
         * @param {Object} row
         * @return {HTMLElement} minimal price html
         */
        getMinValue: function (row) {
            return this._getGiftCardPrice(row)['formatted_prices']['minimal_price'];
        },

        /**
         * UnsanitizedHtml version of getMinValue.
         *
         * @param {Object} row
         * @return {HTMLElement} minimal price html
         */
        getMinValueUnsanitizedHtml: function (row) {
            return this.getMinValue(row);
        }
    });
});
