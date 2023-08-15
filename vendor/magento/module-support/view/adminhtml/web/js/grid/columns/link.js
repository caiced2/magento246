/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_Support/grid/cells/link'
        },

        /**
         * Extracts records' data.
         *
         * @param {Object} record - Record object.
         * @returns {Object}
         */
        getData: function (record) {
            return record[this.index];
        },

        /**
         * Extracts records' link data.
         *
         * @param {Object} record - Record object.
         * @returns {String}
         */
        getHref: function (record) {
            var data = this.getData(record);

            return data.value.link;
        },

        /**
         * Extracts records' label.
         *
         * @param {Object} record - Record object.
         * @returns {String}
         */
        getLabel: function (record) {
            return this.getData(record).label;
        },

        /**
         * Extracts records' size data.
         *
         * @param {Object} record - Record object.
         * @returns {String}
         */
        getSize: function (record) {
            return this.getData(record).size;
        },

        /**
         * Checks if record represents link.
         *
         * @param {Object} record - Record object.
         * @returns {Boolean}
         */
        isLink: function (record) {
            var data = this.getData(record);

            return !!data.value.isLink;
        },

        /**
         * Overrides base method, because this component
         * can't have global field action.
         *
         * @returns {Boolean} False.
         */
        hasFieldAction: function () {
            return false;
        }
    });
});
