/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/columns/column'
], function (_, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_Staging/grid/includes'
        },

        /**
         * Count items.
         *
         * @param {Object} record - Record object.
         * @returns {Number}
         */
        countItems: function (record) {
            var items = this.getItems(record),
                total = 0;

            items.forEach(function (item) {
                total += parseInt(item.count, 10);
            });

            return total;
        },

        /**
         * Extracts records' items.
         *
         * @param {Object} record - Record object.
         * @returns {Object}
         */
        getItems: function (record) {
            return record[this.index];
        },

        /**
         * Extracts item label.
         *
         * @param {Object} item - Item object.
         * @returns {String}
         */
        getItemLabel: function (item) {
            return item.entityLabel;
        }
    });
});
