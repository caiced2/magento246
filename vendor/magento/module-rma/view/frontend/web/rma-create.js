/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'mage/template',
    'jquery-ui-modules/widget'
], function ($, mageTemplate) {
    'use strict';

    $.widget('mage.rmaCreate', {

        /**
         * options with default values
         */
        options: {
            //Template defining selectors
            templateRegistrant: '#template-registrant',
            registrantOptions: '#registrant-options',
            //Template selectors for adding and removing rows
            addItemToReturn: 'add-item-to-return',
            btnRemove: 'btn-remove',
            row: '#row',
            addRow: 'add-row',
            //Return item information selectors
            qtyReqBlock: '#qty_requested_block',
            remQtyBlock: '#remaining_quantity_block',
            remQty: '#remaining_quantity',
            reasonOtherRow: '#reason_other',
            reasonOtherInput: '#items:reason_other',
            radioItem: '#radio:item',
            orderItemId: '#item:order_item_id',
            itemsItem: 'items:item',
            itemsReason: 'items:reason',
            //Default counters and server side variables
            liIndex: 0,
            totalAvlQty: 0,
            availableQuantity: 0,
            formDataPost: null,
            firstItemId: null,
            productType: null,
            shipmentType: null,
            prodTypeBundle: null
        },

        /**
         * Initialize rma create form
         * @private
         */
        _create: function () {
            //On document ready related tasks
            $($.proxy(this._ready, this));
        },

        /**
         * Process and loop thru all form data to create "Items to return" with preselected value.
         * This is used for failed submit.
         * For first time this will add a default row without remove icon/button
         * @private
         */
        _ready: function () {
            this._processFormDataArr(this.options.formDataPost);
            //If no form data , then add default row for Return Item
            if (this.options.liIndex === 0) {
                this._addRegistrant();
                this._showOtherOption('', 0);
            }
        },

        /* eslint-disable */
        /* jscs:disable */
        /**
         * Parse form data and re-create the return item information row preserving the submitted values
         * @private
         * @param {Object} formDataArr
         */
        _processFormDataArr: function (formDataArr) {
            if (formDataArr) {
                var formDataArrlen = formDataArr.length;
                for (var i = 0; i < formDataArrlen; i++) {
                    //Add a row
                    this._addRegistrant();
                    //Set the previously selected values
                    for (var key in formDataArr[i]) {
                        if (formDataArr[i].hasOwnProperty(key)) {
                            if (key === 'order_item_id') {
                                this._setFieldById(this.options.itemsItem + i, formDataArr[i][key]);
                                this._showBundle(i, formDataArr[i][key]);
                                this._setFieldById(this.options.orderItemId.substring(1) + i + '_' + formDataArr[i][key]);
                            } else if (key === 'items') {
                                for (var itemKey in formDataArr[i][key]) {
                                    if (formDataArr[i][key].hasOwnProperty(itemKey)) {
                                        this._setFieldById('items[' + i + '][' + formDataArr[i].order_item_id + '][checkbox][item][' + itemKey + ']');
                                        this._setFieldById('items[' + i + '][' + formDataArr[i].order_item_id + '][checkbox][qty][' + itemKey + ']', formDataArr[i][key][itemKey]);
                                        this._setBundleFieldById(itemKey, formDataArr[i].order_item_id, i);
                                        delete formDataArr[i].qty_requested;
                                    }
                                }
                            } else if (key === 'qty_requested' && formDataArr[i][key] !== '') {
                                this._setFieldById('items:' + key + i, formDataArr[i][key]);
                            } else {
                                this._setFieldById('items:' + key + i, formDataArr[i][key]);

                                if (key === 'reason') {
                                    this._showOtherOption(formDataArr[i][key], i);
                                }
                            }
                        }
                    }
                }
            }
        },

        /* eslint-enable */
        /* jscs:enable */
        /**
         * Add new return item information row using the template
         * @private
         */
        _addRegistrant: function () {
            this._setUpTemplate(this.options.liIndex, this.options.templateRegistrant, this.options.registrantOptions);
            this._showBundle(this.options.liIndex, this.options.firstItemId);
            this._showQuantity(
                this.options.productType,
                this.options.liIndex,
                this.options.availableQuantity,
                this.options.shipmentType
            );
            //Increment after rows are added
            this.options.liIndex++;
        },

        /**
         * Remove return item information row
         * @private
         * @param {String} liIndex - return item information row index
         * @return {Boolean}
         */
        _removeRegistrant: function (liIndex) {
            $(this.options.row + liIndex).remove();

            return false;
        },

        /**
         * Show bundle row for bundle product type
         * @private
         * @param {String} index - return item information row bundle index
         * @param {String} itemId - bundle item id
         */
        _showBundle: function (index, itemId) {
            var rItem, rOrderItemId, typeQty, position;

            $('div[id^="radio\\:item' + index + '_"]').each(function () {
                var $this = $(this);

                if ($this.attr('id')) {
                    $this.parent().hide();
                }
            });

            $('input[id^="items[' + index + ']"]').prop('disabled', true);

            rItem = $(this._esc(this.options.radioItem) + index + '_' + itemId);
            rOrderItemId = $(this._esc(this.options.orderItemId) + index + '_' + itemId);

            if (rItem.length) {
                rItem.parent().show();
                this._enableBundle(index, itemId);
            }

            if (rOrderItemId.length) {
                typeQty = rOrderItemId.attr('rel');
                position = typeQty.lastIndexOf('_');
                this._showQuantity(
                    typeQty.substring(0, position),
                    index,
                    typeQty.substr(position + 1),
                    typeQty.substr(position + 2)
                );
            }
        },

        /**
         * Show quantity block for bundled products
         * @private
         * @param {String} type - product type
         * @param {String} index - return item information row index
         * @param {String} qty - quantity of item specified
         * @param {String} shipmentType - shipment type, useful only for bundle products
         */
        _showQuantity: function (type, index, qty, shipmentType) {
            var qtyReqBlock = $(this.options.qtyReqBlock + '_' + index),
                remQtyBlock = $(this.options.remQtyBlock + '_' + index),
                remQty = $(this.options.remQty + '_' + index);

            if (type === this.options.prodTypeBundle && shipmentType === '1') {
                if (qtyReqBlock.length) {
                    qtyReqBlock.hide();
                }

                if (remQtyBlock.length) {
                    remQtyBlock.hide();
                }
            } else {
                if (qtyReqBlock.length) {
                    qtyReqBlock.show();
                }

                if (remQtyBlock.length) {
                    remQtyBlock.show();
                }

                if (remQty.length) {
                    remQty.text(qty);
                }
            }
        },

        /**
         * Enable bundle and its items
         * @private
         * @param {String} index - return item information row index
         * @param {String} bid - bundle type id
         */
        _enableBundle: function (index, bid) {
            $('input[id^="items[' + index + '][' + bid + '][checkbox][item]["]').prop('disabled', false);
            $('input[id^="items[' + index + '][' + bid + '][checkbox][qty]["]').prop('disabled', function () {
                return !this.value;
            });
        },

        /**
         * Set the value on given element
         * @private
         * @param {String} domId
         * @param {String} value
         */
        _setFieldById: function (domId, value) {
            var x = $('#' + this._esc(domId));

            if (x.length) {
                if (x.is(':checkbox')) {
                    x.attr('checked', true);
                } else if (x.is('option')) {
                    x.attr('selected', 'selected');
                } else {
                    x.val(value);
                }
            }
        },

        /**
         * Used to recreate bundle fields and pre select submitted values on server side errors
         * @private
         * @param {String} id
         * @param {String} bundleID
         * @param {String} index - return item information row index
         */
        _setBundleFieldById: function (id, bundleID, index) {
            this._showBundle(index, bundleID);
            this._showBundleInput(id, bundleID, index);
            this._showQuantity('bundle', index, 0, '1');
        },

        /**
         * Toggle "Other" options
         * @private
         * @param {String} value
         * @param {(String|Number)} index - return item information row index
         */
        _showOtherOption: function (value, index) {
            var resOtherRow = this.options.reasonOtherRow,
                resOtherInput = this._esc(this.options.reasonOtherInput);

            if (value === 'other') {
                $(resOtherRow + index).show();
                $(resOtherInput + index).attr('disabled', false);
            } else {
                $(resOtherRow + index).hide();
                $(resOtherInput + index).attr('disabled', true);
            }
        },

        /**
         * Toggle bundled products
         * @param {String} id - bundle id
         * @param {String} bid - bundle type id
         * @param {String} index - return item information row index
         * @private
         */
        _showBundleInput: function (id, bid, index) {
            var qty = this._esc('#items[' + index + '][' + bid + '][checkbox][qty][' + id + ']');

            if ($(this._esc('#items[' + index + '][' + bid + '][checkbox][item][' + id + ']')).is(':checked')) {
                $(qty).show().attr('disabled', false);
            } else {
                $(qty).hide().attr('disabled', true);
            }
        },

        /**
         * Initialize and create markup for Return Item Information row
         * using the template
         * @private
         * @param {String} index - current index/count of the created template. This will be used as the id
         * @param {String} templateId - template markup selector
         * @param {String} containerId - container where the template will be injected
         * @return {*}
         */
        _setUpTemplate: function (index, templateId, containerId) {
            var li = $('<div/>'),
                tmpl = mageTemplate(templateId);

            li.addClass('fields additional').attr('id', 'row' + index);

            tmpl = tmpl({
                data: {
                    _index_: index
                }
            });

            $(tmpl).appendTo(li);

            $(containerId).append(li);

            // skipping first row
            if (index !== 0) {
                li.addClass(this.options.addRow);
            } else {
                //Hide the close button for first row
                $('#' + this.options.btnRemove + '0').hide();
            }

            //Binding template-wide events handlers
            this.element.on('click', 'button, input:checkbox', $.proxy(this._handleClick, this))
                .on('change', 'select', $.proxy(this._handleChange, this))
                .on('keyup', 'input.input-text', $.proxy(this._handleKeyup, this));

            return li;
        },

        /**
         * Recount remaining quantity for requested bundle items
         * @private
         * @return {integer}
         */
        _recountBundleRemQty: function () {
            var items = {},
                totalReqQty = 0,
                collection = $('.nested input[type="checkbox"]');

            collection.filter(':checked').each(function () {
                var itemId = $(this).data('args').item,
                    itemIndex = $(this).data('args').index,
                    bundleId = $(this).data('args').bundleId,
                    itemReqQty = $('[id="items[' + itemIndex + '][' + bundleId +
                        '][checkbox][qty][' + itemId + ']"]').val() * 1;

                if (typeof items[itemId] === 'undefined') {
                    items[itemId] = itemReqQty;
                } else {
                    items[itemId] += itemReqQty;
                }
            });

            collection.each(function () {
                var itemId = $(this).data('args').item,
                    remQtyColElem = $(this).closest('tr').find('.remaining.qty'),
                    itemAvlQty = parseInt(remQtyColElem.data('args').available, 10),
                    itemReqQty = 0;

                if (typeof items[itemId] !== 'undefined') {
                    itemReqQty = items[itemId];
                }

                remQtyColElem.text(itemAvlQty - itemReqQty);
            });

            $.each(items, function (itemId, itemReqQty) {
                totalReqQty += itemReqQty;
            });

            return totalReqQty;
        },

        /**
         * Recount remaining quantity for requested simple items
         * @private
         * @return {integer}
         */
        _recountSimpleRemQty: function () {
            var items = {},
                totalReqQty = 0,
                options = this.options,
                collection = $('select[id^="' + options.itemsItem + '"]').not('[id="items:item<%- data._index_ %>"]');

            collection.each(function () {
                var itemId = $(this).val(),
                    itemIndex = $(this).data('args').index,
                    itemReqQty = $('[id="items:qty_requested' + itemIndex + '"]').val() * 1;

                if (typeof items[itemId] === 'undefined') {
                    items[itemId] = itemReqQty;
                } else {
                    items[itemId] += itemReqQty;
                }
            });

            collection.each(function () {
                var itemId = $(this).val(),
                    itemIndex = $(this).data('args').index,
                    itemAvlQty = $(this).find(':selected').attr('rel').split('_')[1],
                    itemQtyRequested = $('[id="items:qty_requested' + itemIndex + '"]');

                if (typeof items[itemId] !== 'undefined') {
                    if (itemQtyRequested.valid()) {
                        $(options.remQty + '_' + itemIndex).text(itemAvlQty - items[itemId]);
                    }
                }
            });

            $.each(items, function (itemId, itemReqQty) {
                totalReqQty += itemReqQty;
            });

            return totalReqQty;
        },

        /**
         * Recount remaining quantity for all requested items
         * @private
         */
        _recountRemQty: function () {
            var orderTotalRemQty = this.options.totalAvlQty;

            orderTotalRemQty -= this._recountSimpleRemQty();
            orderTotalRemQty -= this._recountBundleRemQty();

            // Disabling 'Add Item To Return' button if there are no more items available.
            if (orderTotalRemQty > 0) {
                $('#' + this.options.addItemToReturn).prop('disabled', false);
            } else {
                $('#' + this.options.addItemToReturn).prop('disabled', true);
            }
        },

        /* eslint-disable max-depth*/
        /**
         * Delegated handler for click
         * @private
         * @param {Object} e - Native event object
         * @return {Null|Boolean}
         */
        _handleClick: function (e) {
            var currElem = $(e.currentTarget),
                args;

            if (currElem.attr('id') === this.options.addItemToReturn) {
                if (e.handled !== true) {
                    this._addRegistrant();
                    this._recountRemQty();
                    e.handled = true;

                    return false;
                }

            } else if (currElem.hasClass(this.options.btnRemove)) {
                //Extract index
                this._removeRegistrant(currElem.parent().attr('id').replace(this.options.btnRemove, ''));
                this._recountRemQty();

                return false;
            } else if (currElem.attr('type') === 'checkbox') {
                if (currElem.attr('id').match(/^items/)) {
                    args = currElem.data('args');

                    if (args) {
                        this._showBundleInput(args.item, args.bundleId, args.index);
                    }

                    if (currElem.prop('checked') === false) {
                        currElem.closest('tr').find('input[type="number"]').val(0);
                    }

                    this._recountRemQty();
                }
            }
        },

        /* eslint-enable max-depth*/
        /**
         * Delegated handler for change
         * @private
         * @param {Object} e - Native event object
         */
        _handleChange: function (e) {
            var currElem = $(e.currentTarget),
                currId = currElem.attr('id'),
                args = currElem.data('args');

            if (args && currId) {
                if (currId.substring(0, 10) === this.options.itemsItem) {
                    currElem.parent().find('input[type="checkbox"]').prop('checked', false);
                    currElem.parent().find('input[type="number"]').val('');
                    currElem.closest('fieldset').find('[id^="items:qty_requested"]').val(0);

                    this._showBundle(args.index, currElem.val());
                } else if (currId.substring(0, 12) === this.options.itemsReason) {
                    this._showOtherOption(currElem.val(), args.index);
                }

                this._recountRemQty();

                return false;
            }
        },

        /**
         * Delegated handler for keyup
         * @private
         * @param {Object} e - Native event object
         */
        _handleKeyup: function (e) {
            var currElem = $(e.currentTarget),
                currElemId = currElem.attr('id'),
                currElemContainer = currElem.closest('fieldset').find('select[id^="' + this.options.itemsItem + '"]'),
                currElemIndex, orderItemRemQty, orderItemReqQty;

            if (currElemContainer.length < 1) {
                currElemContainer = currElem.closest('fieldset')
                    .siblings('fieldset').find('select[id^="' + this.options.itemsItem + '"]');
            }
            currElemIndex = currElemContainer.data('args').index;

            if (currElemId.match(/^items/) && !e.handled) {
                this._recountRemQty();

                orderItemRemQty = 0;
                orderItemReqQty = parseInt(currElem.val(), 10);

                if (currElem.closest('tr').find('.remaining.qty').length > 0) {
                    orderItemRemQty = parseInt(currElem.closest('tr').find('.remaining.qty').text(), 10);
                } else {
                    orderItemRemQty = parseInt($('[id^="remaining_quantity_' + currElemIndex + '"]').text(), 10);
                }

                if (orderItemRemQty < 0) {
                    currElem.val(orderItemReqQty + orderItemRemQty);
                    this._recountRemQty();
                }

                e.handled = true;
            }
        },

        /**
         * Utility function to add escape chars for jquery selector strings
         * @private
         * @param {String} str - String to be processed
         * @returns {String}
         */
        _esc: function (str) {
            if (str) {
                return str.replace(/([ ;&,.+*~\':"!\^$\[\]()=>|\/@])/g, '\\$1');
            }

            return str;
        }
    });

    return $.mage.rmaCreate;
});
