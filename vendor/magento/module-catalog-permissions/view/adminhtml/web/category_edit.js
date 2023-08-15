/**
 * CatalogPermissions controls for admin category editing
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global Enterprise */
/* eslint-disable strict */
define([
  'jquery',
  'mage/template',
  'mage/validation',
  'prototype'
], function (jQuery, mageTemplate) {

    if (!window.Enterprise) {
        window.Enterprise = {};
    }

    Enterprise.CatalogPermissions = {};

    Enterprise.CatalogPermissions.CategoryTab = Class.create();
    Object.extend(Enterprise.CatalogPermissions.CategoryTab.prototype, {
        /**
         * @param {*} container
         * @param {*} config
         */
        initialize: function (container, config) {
            this.container = $(container);
            this.config = config;
            this.permissions = $H(Object.isArray(this.config.permissions) ? {} : this.config.permissions);
            this.rowTemplate = mageTemplate(this.config.row);
            this.addButton = this.container.down('button.add');
            this.items = this.container.down('.items');
            this.onAddButton = this.handleAddButton.bindAsEventListener(this);
            this.onDeleteButton = this.handleDeleteButton.bindAsEventListener(this);
            this.onChangeWebsiteGroup = this.handleWebsiteGroupChange.bindAsEventListener(this);
            this.onFieldChange = this.handleUpdatePermission.bindAsEventListener(this);

            if (this.addButton) {
                this.addButton.observe('click', this.onAddButton);
            }
            this.index = 1;
            jQuery.validator.addMethod(
                'validate-duplicate-' + this.container.id,
                function (v, elem) {
                    return !$(elem).isDuplicate;
                },
                this.config['duplicate_message']
            );
            this.permissions.each(this.add.bind(this));
        },

        /**
         * Add.
         */
        add: function () {
            var config = {
                    index: this.index++
                },
                params, i, l, isNewRow, readOnly, limitWebsiteIds, row, field, j, ln, fields, deleteButton, wsValue,
                optionId;

            config['html_id'] = this.container.id + '_row_' + config.index;

            readOnly = false;
            isNewRow = true;
            limitWebsiteIds = null;

            if (this.config['limited_website_ids']) {
                limitWebsiteIds = this.config['limited_website_ids'];
            }

            if (this.config['single_mode']) {
                config['website_id_disabled'] = 'disabled="disabled"';
                config['website_id'] = this.config['website_id'];
            }

            /* eslint-disable max-depth, eqeqeq, no-undef, no-cond-assign */
            if (arguments.length) {
                isNewRow = false;
                Object.extend(config, arguments[0].value);

                if (limitWebsiteIds) {
                    //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
                    if (!this.in_array(config['website_id'], limitWebsiteIds)) {
                        readOnly = true;
                    }
                    //jscs:enable requireCamelCaseOrUpperCaseIdentifiers
                }

                params = Object.keys(config);

                for (i = 0, l = params.length; i < l; i++) {
                    if (params[i].match(/grant_/i)) {
                        // Workaround for IE
                        config[params[i] + '_' + config[params[i]]] = 'checked="checked"';

                        if (params[i] == 'grant_catalog_category_view' &&
                            config[params[i]].toString() == '-2'
                        ) {
                            config['grant_catalog_product_price'] = -2;
                            config['grant_catalog_product_price_disabled'] = 'disabled="disabled"';
                        }

                        if (params[i] == 'grant_catalog_product_price' &&
                            config[params[i]].toString() == '-2'
                        ) {
                            config['grant_checkout_items_disabled'] = 'disabled="disabled"';
                        }
                    }
                }
                params.push('id');
                config.id = config['permission_id'];
                config['permission_id'] = arguments[0].key;
            } else {
                config['permission_id'] = 'new_permission' + config.index;
                params = Object.keys(config);
                this.permissions.set(config['permission_id'], {});
            }

            this.items.insert({
                top: this.rowTemplate({
                    data: config
                })
            });

            row = $(config['html_id']);
            row.permissionId = config['permission_id'];
            row.controller = this;

            for (i = 0, l = params.length; i < l; i++) {
                field = row.down('.' + this.fieldClassName(params[i]));

                if (field) {
                    if (!params[i].match(/grant_/i)) {
                        if (field.tagName.toUpperCase() != 'SELECT') {
                            field.value = config[params[i]];
                        } else {
                            for (j = 0, ln = field.options.length; j < ln; j++) {
                                if (config[params[i]] == null) {
                                    config[params[i]] = '-1';
                                }

                                if (field.options[j].value == config[params[i]] &&
                                   field.options[j].value.length == config[params[i]].length
                                ) {
                                    field.value = field.options[j].value;
                                }
                            }
                        }
                    }
                }
            }

            if (arguments.length == 0) {
                row.select('input[value="0"]').each(function (radio) {
                    if (radio.type == 'radio') {
                        radio.checked = true;
                    }
                });
            }

            fields = row.select('input', 'select', 'textarea');

            for (i = 0, l = fields.length; i < l; i++) {
                fields[i].observe('change', this.onFieldChange);

                if (fields[i].type == 'radio') {
                    fields[i].observe('click', this.onFieldChange);
                }

                if (fields[i].hasClassName('permission-duplicate')) {
                    row.duplicateField = fields[i];
                    row.duplicateField.isDuplicate = false;
                    row.duplicateField.addClassName('validate-duplicate-' + this.container.id);
                    row.duplicateField.setAttribute('name', 'validate-duplicate-' + this.container.id);
                }

                if (readOnly) {
                    fields[i].disabled = true;
                }
            }

            if (websiteSelect = row.down('select.website-id-value')) {
                websiteSelect.observe('change', this.onChangeWebsiteGroup);

                if ((isNewRow || !readOnly) && limitWebsiteIds) {
                    for (optionId = 0; optionId < websiteSelect.options.length; optionId++) {
                        wsValue = websiteSelect.options[optionId].value;

                        //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
                        if (wsValue != '' && !this.in_array(wsValue, limitWebsiteIds)) {
                            websiteSelect.remove(optionId);
                        }
                        //jscs:enable requireCamelCaseOrUpperCaseIdentifiers
                    }
                }
            }

            if (groupSelect = row.down('select.customer-group-id-value')) {
                groupSelect.observe('change', this.onChangeWebsiteGroup);
            }

            deleteButton = row.down('button.delete');

            if (deleteButton) {
                if (readOnly) {
                    deleteButton.addClassName('disabled').disabled = true;
                    row.addClassName('readonly');
                } else {
                    deleteButton.observe('click', this.onDeleteButton);
                }
            }

            /* eslint-enable max-depth, eqeqeq, max-depth, eqeqeq, no-undef, no-cond-assign */
            this.modifyParentValue(row);
        },

        /**
         * Handle add button
         */
        handleAddButton: function () {
            this.add();
            this.checkDuplicates();
            this.validate();
        },

        /**
         * @param {*} evt
         */
        handleUpdatePermission: function (evt) {
            var field = $(Event.element(evt)),
                row = field.up('.permission-box'),
                fieldName;

            if (field.name && (field.type != 'radio' || field.checked)) { //eslint-disable-line eqeqeq
                fieldName = field.name.replace(/^(.*)\[([^\]]*)\]$/, '$2');
                this.permissions.get(row.permissionId)[fieldName] = field.value;

            }

            setTimeout(this.disableRadio.bind(this), 1);

            if (field.hasClassName('is-unique')) {
                this.checkDuplicates();
                this.validate();
            }
        },

        /**
         * disable radio.
         */
        disableRadio: function () {
            var rows = this.items.select('.permission-box'),
                i, l, row;

            for (i = 0, l = rows.length; i < l; i++) {
                row = rows[i];

                if (row.hasClassName('readonly')) {
                    continue; //jscs:ignore disallowKeywords
                }

                if (row.down('.' + this.fieldClassName('grant_catalog_category_view') + '[value="-2"]').checked) {
                    row.select('.' + this.fieldClassName('grant_catalog_product_price'))
                        .each(function (item) {
                            item.disabled = true;
                        });
                } else {
                    row.select('.' + this.fieldClassName('grant_catalog_product_price'))
                        .each(function (item) {
                            item.disabled = false;
                        });
                }

                if (row.down('.' + this.fieldClassName('grant_catalog_category_view') + '[value="-2"]').checked ||
                    row.down('.' + this.fieldClassName('grant_catalog_product_price') + '[value="-2"]').checked
                ) {
                    row.select('.' + this.fieldClassName('grant_checkout_items'))
                        .each(function (item) {
                            item.disabled = true;
                        });
                } else {
                    row.select('.' + this.fieldClassName('grant_checkout_items'))
                        .each(function (item) {
                            item.disabled = false;
                        });
                }
            }
        },

        /**
         * @param {*} row
         * @return {Boolean}
         */
        isDuplicate: function (row) {
            var needleString = this.rowUniqueKey(row),
                rows, i, l;

            if (needleString.length === 0 || row.isDeleted) {
                return false;
            }

            rows = this.items.select('.permission-box');

            for (i = 0, l = rows.length; i < l; i++) {
                if (!rows[i].isDuplicate &&
                    !rows[i].isDeleted &&
                    rows[i].permissionId != row.permissionId && this.rowUniqueKey(rows[i]) == needleString //eslint-disable-line
                ) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Check duplicates.
         */
        checkDuplicates: function () {
            var rows = this.items.select('.permission-box'),
                i, l;

            for (i = 0, l = rows.length; i < l; i++) {
                rows[i].duplicateField.isDuplicate = this.isDuplicate(rows[i]);
            }
        },

        /**
         * @param {*} row
         * @return {String}
         */
        rowUniqueKey: function (row) {
            var fields = row.select('select.is-unique', 'input.is-unique'),
                key = '',
                i, l;

            for (i = 0, l = fields.length; i < l; i++) {
                if (fields[i].value === '') {
                    return '';
                }

                key += '_' + fields[i].value;

            }

            return key;
        },

        /**
         * @param {String} fieldName
         * @return {String}
         */
        fieldClassName: function (fieldName) {
            return fieldName.replace(/_/g, '-') + '-value';
        },

        /**
         * @param {*} evt
         */
        handleDeleteButton: function (evt) {
            var button = $(Event.element(evt)),
                row = button.up('.permission-box'),
                elems = row.select('td select', 'td input', 'td textarea'),
                i = 0,
                length = elems.length;

            row.isDeleted = true;
            row.down('.' + this.fieldClassName('_deleted')).value = '1';

            for (; i < length; i++) {
                elems[i].addClassName('ignore-validate');
            }

            row.addClassName('no-display').addClassName('template');
            this.checkDuplicates();
            this.validate();
        },

        /**
         * Validate.
         */
        validate: function () {
            var fields, i, l;

            if (arguments.length > 0) {
                jQuery.validator.validateElement(arguments[0]);

                return;
            }
            fields = this.container.select('input.permission-duplicate');

            for (i = 0, l = fields.length; i < l; i++) {
                jQuery.validator.validateElement(fields[i]);
            }
        },

        /**
         * @param {*} row
         */
        modifyParentValue: function (row) {
            var websiteId, groupId, parentVals, grants, val, key, text;

            /* eslint-disable no-undef, no-cond-assign */

            if (websiteSelect = row.down('select.website-id-value')) {
                websiteId = websiteSelect.value;
            } else if (this.config['single_mode']) {
                websiteId = this.config['website_id'];
            }

            if (!websiteId) {
                websiteId = -1;
            }

            if (groupSelect = row.down('select.customer-group-id-value')) {
                groupId = groupSelect.value;
            }

            if (groupId == '') { //eslint-disable-line eqeqeq
                groupId = -1;
            }
            parentVals = this.config['parent_vals'][websiteId + '_' + groupId];

            if (typeof parentVals == 'undefined') {
                parentVals = {
                    'category': 0,
                    'product': 0,
                    'checkout': 0
                };
            }

            grants = {
                'grant_catalog_category_view': 'category',
                'grant_catalog_product_price': 'product',
                'grant_checkout_items': 'checkout'
            };

            for (key in grants) { //eslint-disable-line guard-for-in
                val = parentVals[grants[key]];

                switch (val) {
                    case '-1':
                        text = this.config['use_parent_allow'];
                        break;

                    case '-2':
                        text = this.config['use_parent_deny'];
                        break;

                    default:
                        text = this.config['use_parent_config'];
                        break;
                }

                row.down('span.' + key).innerHTML = text;
            }

            /* eslint-enable no-undef, no-cond-assign */
        },

        /**
         * @param {*} e
         */
        handleWebsiteGroupChange: function (e) {
            var row = $(Event.element(e)).up('.permission-box');

            this.modifyParentValue(row);
        },

        /**
         * @param {*} needle
         * @param {Array} haystack
         * @return {Boolean}
         */
        'in_array': function (needle, haystack) { //jscs:ignore
            var key;

            for (key in haystack) {
                if (haystack[key] === needle) {
                    return true;
                }
            }

            return false;
        }
    });
});
