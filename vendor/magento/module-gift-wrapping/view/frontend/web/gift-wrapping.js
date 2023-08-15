/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'jquery-ui-modules/widget'
], function ($, mageTemplate) {
    'use strict';

    $.widget('mage.giftWrapping', {

        /**
         * options with default values for setting up the template
         */
        options: {
            //Template containers for Gift wrapping designs and Options
            templateWrapping: '#gift-wrapping-container',
            templateOptions: '#gift-options-container',
            //Input type names for templates
            orderLevelType: 'quote',
            orderItemType: 'quote_item',
            //These options are the Design information set from the backend
            designsInfo: null,
            itemsInfo: null,
            cardInfo: null,
            allowForOrder: null,
            allowGiftReceipt: null,
            allowPrintedCard: null,
            imgBoxSrc: null,
            //Container ids and classes
            addPrintedCardPrefix: 'add-printed-card-',
            noDisplayClass: 'no-display',
            giftWrappingSelectPrefix: 'giftwrapping-',
            imageBoxSelectorPrefix: '#image-box-',
            priceBoxSelectorPrefix: '#price-box-',
            orderContainerPrefix: 'options-order-container',
            optionsItemContainerPrefix: 'options-items-container',
            extraOptionsContainerPrefix: 'extra-options-container',
            addGiftOptionSelectorPrefix: '#add-gift-options-',
            giftOptionsOrderSelectorPrefix: '#add-gift-options-for-order-',
            giftItemsOrderSelectorPrefix: '#add-gift-options-for-items-',
            //Price related selectors
            priceInclTaxSelectorPrefix: '#price-including-tax-',
            priceExclTaxSelectorPrefix: '#price-excluding-tax-',
            regPriceSelectorPrefix: '#regular-price-',
            priceOptionSelectorPrefix: '#price-options-box-'
        },

        /**
         * Initialize gift wrapping containers
         * @private
         */
        _create: function () {
            if (this.options.allowPrintedCard) {
                this.element.on(
                    'click',
                    'input:checkbox[id^="' + this.options.addPrintedCardPrefix + '"]',
                    this.options,
                    this.showCardPrice
                );
            }

            this.element.on(
                'change',
                'select[id^="' + this.options.giftWrappingSelectPrefix + '"]',
                this,
                this.setWrapping
            );
        },

        /**
         * Enable all gift wrapping options based on widget initialization parameters
         * Invoke gift wrapping template processing functions
         * @private
         */
        _ready: function () {
            if (this.options.allowForOrder) {
                this.processGiftOptions();
            }

            this.processGiftOptionItems();

            if (this.options.allowGiftReceipt || this.options.allowPrintedCard) {
                this.processGiftReceiptCardOptions();
            }

            $.each(this.options.itemsInfo.gw, function (index, value) {
                this.element.find('select[id*="-' + index + '"]')
                    .val(value)
                    .trigger('change');
            }.bind(this));
        },

        /**
         * Initialize gift wrapping container if it is already loaded
         * @private
         */
        _init: function () {
            this._ready();
        },

        /**
         * Process and generate gift options/design block for the entire order
         * @public
         */
        processGiftOptions: function () {
            var data = {},
                instance = this;

            $('.' + instance.options.orderContainerPrefix).each(function () {
                data.id = this.id.replace(instance.options.orderContainerPrefix + '-', '');
                data.type = instance.options.orderLevelType;
                data.addrId = false;
                instance.insertBlock(this, data);
                $(instance.options.addGiftOptionSelectorPrefix + data.id +
                    ', ' + instance.options.giftOptionsOrderSelectorPrefix + data.id)
                    .removeClass(instance.options.noDisplayClass);
            });
        },

        /**
         * Process and render gift options/design block for individuals order items
         * @public
         */
        processGiftOptionItems: function () {
            var data = {},
                instance = this;

            $('.' + instance.options.optionsItemContainerPrefix).each(function () {
                var idArr = this.id.replace(instance.options.optionsItemContainerPrefix + '-', '').split('-'),
                    id = idArr[1];

                if (instance.options.itemsInfo[id]) {
                    data.id = id;
                    data.addrId = idArr[0];
                    data.type = instance.options.orderItemType;
                    instance.insertBlock(this, data);
                    $(instance.options.addGiftOptionSelectorPrefix + idArr[0] +
                        ', ' + instance.options.giftItemsOrderSelectorPrefix + idArr[0])
                        .removeClass(instance.options.noDisplayClass);
                }
            });
        },

        /**
         * Template processing for additional options : Printed Card and Gift Receipt
         * @public
         */
        processGiftReceiptCardOptions: function () {
            var instance = this;

            $('.' + instance.options.extraOptionsContainerPrefix).each(function () {
                var id = this.id.replace(instance.options.extraOptionsContainerPrefix + '-', ''),
                    cardInfo = instance.options.cardInfo[id];

                cardInfo = cardInfo || {};
                cardInfo.id = id;
                cardInfo.type = instance.options.orderLevelType;

                instance.insertOptions(this, cardInfo);
                $(instance.options.addGiftOptionSelectorPrefix + id).removeClass(instance.options.noDisplayClass);
            });
        },

        /**
         * Create and insert Gift wrapping option/design block
         * @public
         * @param {Object} element - container for template block
         * @param {Object} data - substitution data
         */
        insertBlock: function (element, data) {
            this._processTemplate(this.options.templateWrapping, element, {
                _id_: data.id,
                _blockId_: data.addrId,
                _type_: data.type
            });
        },

        /**
         * Create and insert Gift wrapping additional options
         * @public
         * @param {Object} element - container for template block
         * @param {Object} data - substitution data
         */
        insertOptions: function (element, data) {
            this._processTemplate(this.options.templateOptions, element, data);
        },

        /**
         * Utility to process templates
         * @private
         * @param {String} templateSelector
         * @param {Object} element - container
         * @param {Object} data - template data
         */
        _processTemplate: function (templateSelector, element, data) {
            var tmpl = $(templateSelector).html();

            if (tmpl && element && data) {
                tmpl = mageTemplate(tmpl, {
                    data: data
                });

                $(tmpl).appendTo(element);
            }
        },

        /**
         * This is the event handler to handle the change event for the gift design select box
         * @public
         * @param {Object} e - event object
         */
        setWrapping: function (e) {
            var instance = e.data,
                $this = $(this),
                designLayer = $this.siblings('div'),
                //trimming first array index from input name like giftwrapping[quote][1][design]
                selectType = e.currentTarget.name.split(']')[0].split('[')[1],
                prefixToTrim = instance.options.giftWrappingSelectPrefix + selectType + '-',
                designBlockId = $this.prop('id').replace(prefixToTrim, ''),
                blockId = $this.data('addrId'),
                designInfo = instance.options.designsInfo[this.value];

            //If a design is selected in the drop down, render it with price
            if (this.value) {
                instance.setDesign(designInfo.path, designLayer.find('img'))
                    .setPrice(
                        instance.options.itemsInfo[designBlockId],
                        designInfo,
                        designBlockId,
                        blockId,
                        designLayer
                    );
                $(instance.options.priceBoxSelectorPrefix + designBlockId).removeClass(instance.options.noDisplayClass);
            }

            //Based on design selection toggle design layer display
            designLayer.toggleClass(instance.options.noDisplayClass, !this.value);
        },

        /**
         * Set the image path based on provided source
         * @public
         * @param {String} path - path to the design image
         * @param {Object} $img - jQuery design image object
         * @return {Object} context for chaining
         */
        setDesign: function (path, $img) {
            $img.prop('src', path ? path : this.options.imgBoxSrc);

            return this;
        },

        /**
         * Function to handle Design pricing
         * @public
         * @param {Object} itemsInfo - Individual item information object containing price attributes
         * @param {Object} designInfo - Individual design information object containing price attributes
         * @param {String} designBlockId - design block id
         * @param {String} addrId - quote address item id
         * @param {Object} designLayer - object where changes should be made
         * @returns {Object} context for chaining
         */
        setPrice: function (itemsInfo, designInfo, designBlockId, addrId, designLayer) {
            var blockId = parseInt(addrId, 10) ? addrId : designBlockId,
                price = itemsInfo && itemsInfo.price ? itemsInfo.price : designInfo[blockId].price,
                priceInclTax = itemsInfo && itemsInfo['price_incl_tax'] ?
                    itemsInfo['price_incl_tax'] : designInfo[blockId]['price_incl_tax'],
                priceExclTax = itemsInfo && itemsInfo['price_excl_tax'] ?
                    itemsInfo['price_excl_tax'] : designInfo[blockId]['price_excl_tax'];

            if (price || priceInclTax && priceExclTax) {
                designLayer.find(this.options.priceInclTaxSelectorPrefix + designBlockId).text(priceInclTax);
                designLayer.find(this.options.priceExclTaxSelectorPrefix + designBlockId).text(priceExclTax);
                designLayer.find(this.options.regPriceSelectorPrefix + designBlockId).text(price);
            }

            return this;
        },

        /**
         * This is the handler function to handle the checkbox toggle additional gift option for Printed Card
         * @public
         * @param {Object} e - event object
         */
        showCardPrice: function (e) {
            $(e.data.priceOptionSelectorPrefix + this.id.replace(e.data.addPrintedCardPrefix, ''))
                .toggleClass(e.data.noDisplayClass, !this.checked);
        }

    });

    return $.mage.giftWrapping;
});
