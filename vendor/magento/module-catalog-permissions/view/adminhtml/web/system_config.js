/**
 * CatalogPermissions control for admin system configuration field
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global Enterprise */
/* eslint-disable strict */
define([
    'prototype'
], function () {

    if (!window.Enterprise) {
        window.Enterprise = {};
    }

    if (!Enterprise.CatalogPermissions) {
        Enterprise.CatalogPermissions = {};
    }

    Enterprise.CatalogPermissions.Config = Class.create();

    Object.extend(Enterprise.CatalogPermissions.Config.prototype, {
        /**
         * Initialize.
         */
        initialize: function () {
            Event.observe(window.document, 'dom:loaded', this.handleDomLoaded.bindAsEventListener(this));
        },

        /**
         * Handle Dom Loaded.
         */
        handleDomLoaded: function () {
            $$('.magento-grant-select').each(function (element) {
                element.observe('change', this.updateFields.bind(this));
            }, this);

            this.updateFields();
        },

        /**
         * Update Fields.
         */
        updateFields: function () {
            $$('.magento-grant-select').each(function (element) {
                if (parseInt(element.value) !== 2) { //eslint-disable-line radix
                    element.up('tr').next('tr').hide();
                } else {
                    element.up('tr').next('tr').show();
                }

                if (element.hasClassName('browsing-catagories')) {
                    if (parseInt(element.value) === 1) { //eslint-disable-line radix
                        element.up('tr').next('tr', 1).hide();
                    } else {
                        element.up('tr').next('tr', 1).show();
                    }
                }
            });
        }
    });

    new Enterprise.CatalogPermissions.Config();
});
