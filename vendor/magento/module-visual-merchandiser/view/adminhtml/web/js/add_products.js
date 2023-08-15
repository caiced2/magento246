/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*global setLocation:true*/
/*@api*/
define([
    'jquery',
    'Magento_Ui/js/core/app',
    'uiRegistry',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, bootstrap, registry) {
    'use strict';

    $.widget('mage.visualMerchandiserAddProducts', {
        options: {
            dialogUrl: null,
            dialogButton: null
        },
        isGridLoaded: false,
        registry: null,
        positionCacheName: 'position_cache_valid',
        selectedFromCache: 'selected_cache',
        massAssign: 'mass_assign',
        saveButtonName: 'add_products_save_button',

        /**
         * @private
         */
        _create: function () {
            this.registry = registry;
            this.bootstrap = bootstrap;

            this.element.modal(this._getConfig());
            this._bind();
        },

        /**
         * @private
         */
        _bind: function () {
            this._on({
                requestUpdate: this.updateGrid,
                requestReload: this.reloadGrid
            });

            $(document).on('click', this.options.dialogButton, $.proxy(this.openDialog, this));
        },

        /**
         * Open the dialog
         */
        openDialog: function () {
            this.element.modal('openModal');
        },

        /**
         * Close the dialog
         */
        closeDialog: function () {
            this.element.modal('closeModal');
        },

        /**
         * Update the grid with changes
         */
        updateGrid: function () {
            this.registry.get('merchandiser_product_listing.merchandiser_product_listing_data_source').reload({
                refresh: true
            });
        },

        /**
         * Toggle state of submit button.
         *
         * @param {Boolean} disabled
         */
        toggleSaveButton: function (disabled) {
            $(document).find('button[name="' + this.saveButtonName + '"]')
                .attr('disabled', disabled)
                .text(disabled ? $.mage.__('Wait loading...') : $.mage.__('Save and Close'));
        },

        /**
         * Grid load handler.
         */
        onGridLoad: function () {
            var self = this;

            this.isGridLoaded = true;
            this.registry.get(
                'merchandiser_product_listing.merchandiser_product_listing_data_source',
                function (listingDataSource) {
                    listingDataSource.on('reload', function () {
                        self.toggleSaveButton(true);
                    });
                    listingDataSource.on('reloaded', function () {
                        self.toggleSaveButton(false);
                    });
                }
            );
        },

        /**
         * Perform a full grid update, caches will be invalidated
         * changes to grid will be lost.
         *
         * @param {Event} event
         * @param {Array} action
         */
        reloadGrid: function (event, action) {
            var selectedFromCache = registry.get(this.selectedFromCache),
                indexOfCacheResult,
                i;

            if (action.action === 'remove') {
                selectedFromCache = JSON.parse(selectedFromCache);

                for (i = 0; i < action.ids.length; i++) {
                    indexOfCacheResult = selectedFromCache.indexOf(action.ids[i]);
                    indexOfCacheResult >= 0 && selectedFromCache.splice(indexOfCacheResult, 1);
                }

                registry.set(this.selectedFromCache, JSON.stringify(selectedFromCache));
                registry.set(this.massAssign, true);
                this.updateGrid();
            } else if (action.action === 'assign') {
                selectedFromCache = JSON.parse(selectedFromCache);

                for (i = 0; i < action.ids.length; i++) {
                    selectedFromCache.indexOf(action.ids[i]) !== -1 || selectedFromCache.push(action.ids[i]);
                }

                registry.set(this.selectedFromCache, JSON.stringify(selectedFromCache));
                registry.set(this.massAssign, true);
                this.updateGrid();
            } else {
                this._invalidateCache();
                this.updateGrid();
            }
        },

        /**
         * Invalidate grid selection cache
         * @private
         */
        _invalidateCache: function () {
            this.registry.set(this.positionCacheName, false);
            this.registry.set(this.selectedFromCache, JSON.stringify([]));
        },

        /**
         * @returns {{type: String, title: String, opened: *, buttons: *}}
         * @private
         */
        _getConfig: function () {
            return {
                title: $.mage.__('Add Products'),
                opened: $.proxy(this._opened, this),
                buttons: this._getButtonsConfig()
            };
        },

        /**
         * @private
         */
        _opened: function () {
            if (!this.isGridLoaded) {
                this._invalidateCache();
                $.ajax({
                    type: 'GET',
                    url: this.options.dialogUrl,
                    context: $('body'),
                    dataType: 'json',
                    success: $.proxy(this._ajaxSuccess, this)
                });
            } else {
                this._invalidateCache();
                this.updateGrid();
            }
        },

        /**
         * @param {String} data
         * @private
         */
        _ajaxSuccess: function (data) {
            this._validateAjax(data);
            this.bootstrap(data);
            this.onGridLoad();
        },

        /**
         * @param {Object} response
         * @private
         */
        _validateAjax: function (response) {
            if (response.ajaxExpired && response.ajaxRedirect) {
                setLocation(response.ajaxRedirect);
            } else if (response.url) {
                setLocation(response.url);
            }
        },

        /**
         * @returns {*[]}
         * @private
         */
        _getButtonsConfig: function () {
            return [{
                text: $.mage.__('Wait loading...'),
                class: '',
                click: $.proxy(this._save, this),
                attr: {
                    name: this.saveButtonName,
                    disabled: 'disabled'
                }
            }];
        },

        /**
         * @private
         */
        _save: function () {
            var idColumn = this
                .registry
                .get('merchandiser_product_listing.merchandiser_product_listing.merchandiser_product_columns.ids');

            this._invalidateCache();

            this._trigger('dialogSave', null, [
                idColumn.selected(),
                this
            ]);
        }
    });

    return $.mage.visualMerchandiserAddProducts;
});
