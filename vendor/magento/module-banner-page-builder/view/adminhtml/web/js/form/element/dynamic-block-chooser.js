/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_PageBuilder/js/form/element/block-chooser',
    'mage/translate'
], function (BlockChooser, $t) {
    'use strict';

    return BlockChooser.extend({
        defaults: {
            metaRowIndex: 1
        },

        /**
         * Retrieves the classes that should be applied to the next created meta row
         * @return {String}
         */
        getNextMetaRowClasses: function () {
            return 'data-row' + (this.metaRowIndex++ % 2 === 0 ? '' : ' _odd-row');
        },

        /**
         * Determines the status label for the currently loaded block
         *
         * @returns {String}
         */
        getStatusLabel: function () {
            return this.meta()['is_enabled'] === '1' ? $t('Enabled') : $t('Disabled');
        },

        /**
         * Generates the customer segments display value
         * @return {Array}
         */
        getCustomerSegments: function () {
            return this.meta()['customer_segments'].length > 0
                ? this.meta()['customer_segments']
                : [$t('All Segments')];
        },

        /**
         * Generates the catalog rules display value
         * @return {Array}
         */
        getRelatedCatalogRules: function () {
            return this.meta()['related_catalog_rules'];
        },

        /**
         * Generates the cart rules display value
         * @return {Array}
         */
        getRelatedCartRules: function () {
            return this.meta()['related_cart_rules'];
        }
    });
});
