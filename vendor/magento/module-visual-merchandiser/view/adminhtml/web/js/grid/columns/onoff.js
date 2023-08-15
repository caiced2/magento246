/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'mage/translate',
    'Magento_Ui/js/grid/columns/multiselect',
    'uiRegistry',
    'prototype'
], function (_, $t, Column, registry) {
    'use strict';

    return Column.extend({
        defaults: {
            headerTmpl: 'ui/grid/columns/onoff',
            bodyTmpl: 'ui/grid/cells/onoff',
            fieldClass: {
                'admin__scope-old': true,
                'data-grid-onoff-cell': true,
                'data-grid-checkbox-cell': false
            },
            imports: {
                selectedData: '${ $.provider }:data.selectedData',
                allIds: '${ $.provider }:data.allIds'
            },
            listens: {
                '${ $.provider }:reloaded': 'setDefaultSelections'
            }
        },

        /**
         * @param {Number} id
         * @returns {*}
         */
        getLabel: function (id) {
            return this.selected.indexOf(id) !== -1 ? $t('On') : $t('Off');
        },

        /**
         * Sets the ids for preselected elements
         * @returns void
         */
        setDefaultSelections: function () {
            var positionCacheValid = registry.get('position_cache_valid'),
                selectedFromCache = registry.get('selected_cache'),
                massAssign = registry.get('mass_assign');

            if (positionCacheValid && massAssign === true) {
                // Set selected data from cache
                selectedFromCache = JSON.parse(selectedFromCache);

                this.selected(_.clone(selectedFromCache));

                registry.set('position_cache_valid', true);
                registry.set('selected_cache', JSON.stringify(this.selected()));
                registry.set('mass_assign', false);

                return;
            }

            if (positionCacheValid && this.selected().length === 0) {
                // Load selected data from cache
                selectedFromCache = JSON.parse(selectedFromCache);

                this.selected(_.clone(selectedFromCache));

                registry.set('position_cache_valid', true);
                registry.set('selected_cache', JSON.stringify(this.selected()));

                return;
            }

            if (positionCacheValid && this.selected().length > 0) {
                // Save selected data to cache
                registry.set('position_cache_valid', true);
                registry.set('selected_cache', JSON.stringify(this.selected()));

                return;
            }

            if (this.selectedData.length === 0) {
                // Remove all selected data
                this.selected.removeAll();
                registry.set('position_cache_valid', true);
                registry.set('selected_cache', JSON.stringify([]));

                return;
            }

            this.selected.removeAll();
            this.selected(_.keys(this.selectedData));

            registry.set('position_cache_valid', true);
            registry.set('selected_cache', JSON.stringify(this.selected()));
        },

        /**
         * Show/hide action in the massaction menu
         * @param {Number} actionId
         * @returns {Boolean}
         */
        isActionRelevant: function (actionId) {
            var relevant = true;

            switch (actionId) {
                case 'selectPage':
                    relevant = !this.isPageSelected(true);
                    break;

                case 'deselectPage':
                    relevant =  this.isPageSelected();
                    break;
            }

            return relevant;
        },

        /**
         * Updates values of the 'allSelected'
         * and 'indetermine' properties.
         *
         * @returns {Object} Chainable.
         */
        updateState: function () {
            var positionCacheValid = registry.get('position_cache_valid'),
                totalRecords    = this.totalRecords(),
                totalSelected   = this.totalSelected(),
                allSelected;

            if (positionCacheValid && this.selected().length > 0) {
                registry.set('position_cache_valid', true);
                registry.set('selected_cache', JSON.stringify(this.selected()));
            }

            // When filters are enabled then totalRecords is unknown
            if (this.getFiltering()) {
                if (this.getFiltering().search !== '') {
                    totalRecords = -1;
                }
            }

            allSelected = totalRecords && totalSelected === totalRecords;

            this.allSelected(allSelected);
            this.indetermine(totalSelected && !allSelected);

            return this;
        },

        /**
         * Selects all records, even those that
         * are not visible on the page.
         *
         * @returns {Object} Chainable.
         */
        selectAll: function () {
            var newSelected = [],
                $selected = $H(),
                i;

            for (i = 0; i < this.selected().length; i++) {
                $selected.set(this.selected()[i], i);
            }

            for (i = 0; i < this.allIds.length; i++) {
                if ($selected.get(this.allIds[i]) === undefined) {
                    newSelected.push(this.allIds[i]);
                }
            }

            this.selected(this.selected().concat(newSelected));

            return this;
        }
    });
});
