/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
], function (_, SelectColumn) {
    'use strict';

    return SelectColumn.extend({
        defaults: {
            updateTypesMap: []
        },

        /** @inheritdoc **/
        initialize: function () {
            return this._super()
                    .processingUpdateTypesMap();
        },

        /**
         * Processing update types -
         * add labels to map
         *
         * @returns {Object} Chainable
         */
        processingUpdateTypesMap: function () {
            var optionData;

            this.updateTypesMap.forEach(function (data) {
                optionData = _.findWhere(this.options, {
                    value: data.value
                });
                data.label = optionData ? optionData.label : '';
            }, this);

            return this;
        },

        /**
         * Overwrite parent method -
         * add class to highlight column
         *
         * @returns {Object} Classes
         */
        getFieldClass: function (record) {
            var data = _.findWhere(this.updateTypesMap, {
                    value: record[this.index].toString()
                });

            if (data) {
                this.resetFieldClass();
                this.fieldClass[data.className] = true;
            }

            return this.fieldClass;
        },

        /**
         * Clean field classes for update types.
         */
        resetFieldClass: function () {
            _.each(this.updateTypesMap, function (type) {
                this.fieldClass[type.className] = false;
            }, this);
        }
    });
});
