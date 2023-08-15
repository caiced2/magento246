/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Add By SKU class
 *
 * @method submitConfigured()
 * @method removeAllFailed()
 */
/* global AddBySku, productConfigure, parseNumber, FORM_KEY */
/* eslint-disable strict */
define([
    'prototype',
    'mage/translate',
    'Magento_Catalog/catalog/product/composite/configure',
    'Magento_Catalog/js/price-utils'
], function (prototype, translate, configure, priceUtils) {

    window.AddBySku = Class.create();

    AddBySku.prototype = {
        /**
         * Constructor
         *
         * @param {*} order - Instance of AdminOrder
         * @param {Object} data - Array (see initialize())
         */
        initialize: function (order, data) {
            var originConfiguredCheck, originRequestConfiguration;

            if (!data) {
                data = {};
            }
            this.lastId = 0;
            this.configuredSkus = [];
            this.configurableItems = {};
            this.dataContainerId = data.dataContainerId;
            this.deleteButtonHtml = data.deleteButtonHtml;
            this.order = order;
            this.listType = data.listType;
            this.errorGridId = data.errorGridId;
            this.fileFieldName = data.fileFieldName;
            this.fileUploadUrl = data.fileUploadUrl;
            this.fileUploadedParamName = data.fileUploaded;

            // Changing original productConfigure object for SKU items needs
            productConfigure.skuObject = this;
            originConfiguredCheck = productConfigure.itemConfigured;

            /**
             * @param {*} listType
             * @param {*} itemId
             * @return {*}
             */
            productConfigure.itemConfigured = function (listType, itemId) {
                var indexOfItemId;

                if (listType != this.skuObject.listType) { //eslint-disable-line eqeqeq
                    return originConfiguredCheck.apply(this, [listType, itemId]);
                }

                indexOfItemId = this.skuObject.configuredSkus.indexOf(itemId);

                if (indexOfItemId !== -1) {
                    if (!originConfiguredCheck.apply(this, [listType, itemId])) {
                        this.skuObject.configuredSkus.splice(indexOfItemId, 1);

                        return false;
                    }

                    return true;
                }

                return false;
            };
            originRequestConfiguration = productConfigure._requestItemConfiguration;

            /**
             * @param {*} listType
             * @param {*} itemId
             * @return {*}
             * @private
             */
            productConfigure._requestItemConfiguration = function (listType, itemId) {
                if (listType == this.skuObject.listType) { //eslint-disable-line eqeqeq
                    itemId = this.skuObject.configurableItems[itemId];
                }

                return originRequestConfiguration.apply(this, [listType, itemId]);
            };

            /**
             * Abstract admin sales instance.
             *
             * @param {Object} addBySkuObject
             */
            function adminSalesInstance(addBySkuObject) {
                var fields, i;

                this.skuInstance = addBySkuObject;
                this.order = addBySkuObject.order;

                /**
                 * Submit configured.
                 */
                this.submitConfigured = function () {};

                /**
                 * Update error grid.
                 */
                this.updateErrorGrid = function () {};

                /**
                 * On submit sku form
                 */
                this.onSubmitSkuForm = function () {};
                fields = $$(
                    '#' + addBySkuObject.dataContainerId + ' input[name="sku"]',
                    '#' + addBySkuObject.dataContainerId + ' input[name="qty"]'
                );

                for (i = 0; i < fields.length; i++) {
                    Event.observe(fields[i], 'keypress', addBySkuObject.formKeyPress.bind(addBySkuObject));
                }
            }

            /* eslint-disable */
            /* jscs:disable */
            // admin sales instance for 'Manage shopping cart'
            adminCheckout.prototype = new adminSalesInstance(this);
            adminCheckout.prototype.constructor = adminCheckout;
            function adminCheckout()
            {
                this.controllerRequestParameterNames = {customerId: 'customer', storeId: 'store'};
            }

            /* eslint-enable */
            /* jscs:enable */

            /**
             * Submit configured.
             */
            adminCheckout.prototype.submitConfigured = function () {
                // Save original source grids configuration to be restored later
                var oldSourceGrids = this.order.sourceGrids,
                    parentResponseHandler;

                // Leave only error grid (don't submit information from other grids right now)
                this.order.sourceGrids = {
                    'sku_errors': this.skuInstance.errorSourceGrid
                };
                // Save old response handler function to override it
                parentResponseHandler = this.order.loadAreaResponseHandler;

                /**
                 * @param {Object} response
                 */
                this.order.loadAreaResponseHandler = function (response) {
                    if (!response.errors) {
                        // If response is empty loadAreaResponseHandler() won't update the area
                        response.errors = '<span></span>';
                    }
                    // call origin response handler function
                    parentResponseHandler.call(this, response);
                };
                this.order.productGridAddSelected('sku');
                this.order.sourceGrids = oldSourceGrids;
            };

            /**
             * @param {Object} params
             */
            adminCheckout.prototype.updateErrorGrid = function (params) {
                var oldLoadingAreas = this.order.loadingAreas,
                    url;

                // We need to override this field, otherwise layout is going to be broken
                this.order.loadingAreas = 'errors';
                url = this.order.loadBaseUrl + 'block/' + this.skuInstance.listType;

                if (!params.json) {
                    params.json = true;
                }
                new Ajax.Request(url, {
                    parameters: this.order.prepareParams(params),
                    loaderArea: 'html-body',
                    onSuccess: function (transport) {
                        var response = transport.responseText.evalJSON();

                        if (!response.errors) {
                            // If response is empty loadAreaResponseHandler() won't update the area
                            response.errors = '<span></span>';
                        }
                        this.loadAreaResponseHandler(response);
                    }.bind(this.order),
                    onComplete: function () {
                        this.loadingAreas = oldLoadingAreas;
                    }.bind(this.order)
                });
            };

            /* eslint-disable */
            /* jscs:disable */
            // admin sales instance for order creation
            adminOrder.prototype = new adminSalesInstance(this);
            adminOrder.prototype.constructor = adminOrder;
            /**
             * Admin order.
             */
            function adminOrder() {
                var skuAreaId = this.order.getAreaId('additional_area');

                this.controllerRequestParameterNames = {
                    customerId: 'customerId', storeId: 'storeId'
                };
                this.order.itemsArea.skuButton = new ControlButton(jQuery.mage.__('Add Products By SKU'));
                this.order.itemsArea.skuButton.onClick = function () {
                    $(skuAreaId).show();
                    var el = this;
                    window.setTimeout(function () {
                        el.remove();
                    }, 10);
                };
                this.order.itemsArea.onLoad = this.order.itemsArea.onLoad.wrap(function(proceed) {
                    proceed();
                    if (!$(skuAreaId).visible()) {
                        this.addControlButton(this.skuButton);
                    }
                });
                this.order.dataArea.onLoad();
            }

            /* eslint-enable */
            /* jscs:enable */
            /**
             * Submit configured
             */
            adminOrder.prototype.submitConfigured = function () {
                var area = ['errors', 'search', 'items', 'shipping_method', 'totals', 'giftmessage','billing_method'],
                    table = $('sku_errors_table'),
                    elements = table.select('input[type=checkbox][name=sku_errors]:checked'),
                    fieldsPrepare = {};

                fieldsPrepare['from_error_grid'] = '1';
                elements.each(function (elem) {
                    var tr;

                    if (!elem.value || elem.value == 'on') { //eslint-disable-line eqeqeq
                        return;
                    }
                    tr = elem.up('tr');

                    if (tr) {
                        (function (fieldNames, parent, id) {
                            var i, el, paramKey;

                            if (typeof fieldNames == 'string') {
                                fieldNames = [fieldNames];
                            }

                            for (i = 0; i < fieldNames.length; i++) {
                                el = parent.select('input[name=' + fieldNames[i] + ']');
                                paramKey = 'add_by_sku[' + id + '][' + fieldNames[i] + ']';

                                if (el.length) {
                                    fieldsPrepare[paramKey] = el[0].value;
                                }
                            }
                        })(['qty', 'sku'], tr, elem.value);
                    }
                });
                this.order.productConfigureSubmit('errors', area, fieldsPrepare, this.skuInstance.configuredSkus);
                this.skuInstance.configuredSkus = [];
            };

            /**
             * @param {Object} params
             */
            adminOrder.prototype.updateErrorGrid = function (params) {
                this.order.loadArea('errors', true, params);
            };

            /**
             * On submit sku form.
             */
            adminOrder.prototype.onSubmitSkuForm = function () {
                this.order.additionalAreaButton && Element.show(this.order.additionalAreaButton);
                this.order.itemsArea.addControlButton(this.order.itemsArea.skuButton);
            };

            // Strategy
            if (this.order instanceof (window.AdminOrder || Function)) {
                this._provider = new adminOrder(); //jscs:ignore requireCapitalizedConstructors
            } else {
                this._provider = new adminCheckout(); //jscs:ignore requireCapitalizedConstructors
            }
            this.controllerRequestParameterNames = this._provider.controllerRequestParameterNames;
        },

        /**
         * @param {Object} obj
         * @return {Boolean}
         */
        removeFailedItem: function (obj) {
            var sku;

            try {
                sku = obj.up('tr').select('td')[0].select('input[name="sku"]')[0].value;
                this._provider.updateErrorGrid({
                    'remove_sku': sku
                });
            } catch (e) {
                return false;
            }
        },

        /**
         * remove all failed.
         */
        removeAllFailed: function () {
            this._provider.updateErrorGrid({
                'sku_remove_failed': '1'
            });
        },

        /**
         * Submit configured.
         */
        submitConfigured: function () {
            this._provider.submitConfigured();
        },

        /**
         * Delete element from queue
         *
         * @param {Object} obj - Element to remove
         */
        del: function (obj) {
            var tr = obj.up('tr'),
                itemId, newElement;

            if ($('id_' + tr.id)) {
                itemId = $('id_' + tr.id).value;
                newElement = document.createElement('input');
                newElement.type = 'hidden';
                newElement.value = itemId;
                newElement.name = 'deleteSku[]';
                $(this.dataContainerId).appendChild(newElement);
            }
            tr.remove();
        },

        /**
         * Submit selected CSV file (if any)
         */
        submitSkuForm: function () {
            var $form, $file, requestParams, sku, i;

            this._provider.onSubmitSkuForm();

            // Hide 'add by SKU' area on order creation page (not available on manage shopping cart page)
            this.order.hideArea && this.order.hideArea('additional_area');
            $form = new Element('form', {
                'action': this.fileUploadUrl,
                'method': 'post',
                'enctype': 'multipart/form-data'
            });

            $form.insert(new Element('input', {
                'type': 'hidden',
                'name': this.fileUploadedParamName,
                'value': '0'
            }));
            $file = Element.select('body', 'input[name="' + this.fileFieldName + '"]')[0];

            if ($file.value) {
                // Inserting element to other place removes
                // it from the old one. Creating new file input element on same place
                // to avoid confusing effect that it has disappeared.
                $file.up().insert(new Element('input', {
                    'type': 'file',
                    'name': this.fileFieldName
                }));
                // We need to insert same file input element into the form. Simple copy of name/value doesn't work.
                $form.insert($file);
                $form[this.fileUploadedParamName].value = '1';
            }

            // sku form rows
            requestParams = {};
            sku = '';
            // eslint-disable-next-line jquery-no-input-event-shorthand
            $('sku_table').select('input[type=text]').each(function (elem) {
                var qty = 0,
                    paramKey;

                if (elem.name == 'sku') { //eslint-disable-line eqeqeq
                    sku = elem.value;
                } else if (elem.name == 'qty') { //eslint-disable-line eqeqeq
                    qty = elem.value;
                } else {
                    return;
                }

                if (sku != '') { //eslint-disable-line eqeqeq
                    paramKey = 'add_by_sku[' + sku + '][qty]';

                    if (paramKey in requestParams) {
                        requestParams[paramKey] = parseNumber(requestParams[paramKey]) + parseNumber(qty);
                    } else {
                        requestParams[paramKey] = qty;
                    }
                }
            });

            if (!Object.keys(requestParams).length && !$file.value) {
                return false;
            }

            for (i in requestParams) { //eslint-disable-line guard-for-in
                $form.insert(new Element('input', {
                    'type': 'hidden',
                    'name': i,
                    'value': requestParams[i]
                }));
            }

            // general fields
            $form.insert(new Element('input', {
                'type': 'hidden',
                'name': this.controllerRequestParameterNames.customerId,
                'value': this.order.customerId
            }));
            $form.insert(new Element('input', {
                'type': 'hidden',
                'name': this.controllerRequestParameterNames.storeId,
                'value': this.order.storeId
            }));
            $form.insert(new Element('input', {
                'type': 'hidden',
                'name': 'quote_id',
                'value': this.order.quoteId
            }));
            $form.insert(new Element('input', {
                'type': 'hidden',
                'name': 'form_key',
                'value': FORM_KEY
            }));

            // For IE we must make the form part of the DOM, otherwise browser refuses to submit it
            Element.select(document, 'body')[0].insert($form);
            $form.submit(); //eslint-disable-line jquery-no-input-event-shorthand

            // Show loader
            jQuery($form).trigger('processStart');

            return true;
        },

        /**
         * Configure a product
         *
         * @param {*} id - Product ID
         */
        configure: function (id, sku) {
            // eslint-disable-next-line jquery-no-input-event-shorthand
            var productRow = $('sku_errors_table').select('div[id=sku_' + sku + ']')[0],
                noticeElement = productRow.select('.message-notice'),
                productQtyElement = productRow.up('tr').select('input[name=qty]')[0],
                productPriceElement = productRow.up('tr').select('td.col-price')[0];

            if (typeof this.configurableItems[sku] === 'undefined') {
                this.configurableItems[sku] = id;
            }

            // Don't process configured element by addBySku() observer method (it won't be serialized by serialize())
            productConfigure.setConfirmCallback(this.listType, function () {
                var $qty, $qtyElements, optionPrice, productPrice = productPriceElement.innerText;

                // It is vital to push string element, check this line in configure.js:
                // this.itemsFilter[listType].indexOf(itemId) != -1
                productConfigure.skuObject.configuredSkus.push(sku.toString());

                if (noticeElement.length) {
                    // Remove message saying product requires configuration
                    noticeElement[0].remove();
                }
                $qty = productConfigure.getCurrentConfirmedQtyElement();
                optionPrice = productConfigure.getCurrentConfirmedSelectElement();
                // eslint-disable-next-line jquery-no-input-event-shorthand
                $qtyElements = $('super-product-table') ? $('super-product-table').select('input.qty') : [];

                if (optionPrice.length !== undefined &&
                    optionPrice[optionPrice.length - 1] !== undefined &&
                    optionPrice[optionPrice.length - 1].hasAttribute('price')
                ) {
                    productPrice = priceUtils.formatPrice(
                        optionPrice[optionPrice.length - 1].readAttribute('price'),
                        window.ProductConfigure.spConfig.config.priceFormat
                    );
                }

                if ($qty) { // Product set does not have this
                    // Synchronize qtys between configure window and grid
                    productQtyElement.value = $qty.value;
                    productPriceElement.innerText = productPrice;
                } else if ($(productConfigure.confirmedCurrentId)) {
                    productQtyElement.value = '';
                    productPriceElement.value = productPriceElement.innerText;

                    $qtyElements.forEach(function (element) {
                        if (parseFloat(element.value) > 0) {
                            productQtyElement.value = 1;

                            return;
                        }
                    });
                }
            });
            productConfigure.showItemConfiguration(this.listType, sku);
            productConfigure.setShowWindowCallback(this.listType, function () {
                // sync qty of grid and qty of popup
                var qty = productQtyElement.value,
                    formCurrentQty;

                if (qty && !isNaN(qty)) {
                    formCurrentQty = productConfigure.getCurrentFormQtyElement();

                    if (formCurrentQty) {
                        formCurrentQty.value = qty;
                    }
                }
            });
        },

        /**
         * Intercept click on "Add to cart" button and submit sku instead of executing original action
         */
        observeAddToCart: function () {
            var that = this;

            this.addToCartButtonEvents = [];
            // eslint-disable-next-line jquery-no-input-event-shorthand
            $('products_search').select('button.button-to-cart').each(function (button) {
                var originalOnclick;

                // Save original event
                that.addToCartButtonEvents[button.id] = button.onclick;
                originalOnclick = button.onclick;

                /**
                 * Submit CSV file or perform an original event
                 */
                button.onclick = function () {
                    that.submitSkuForm() || originalOnclick();
                    that.clearAddForm();
                };
            });
        },

        /**
         * Return add form to untouched state
         */
        clearAddForm: function () {
            // eslint-disable-next-line jquery-no-input-event-shorthand
            var $rows = $(this.dataContainerId).select('tr'),
                rowNum = $rows.length,
                i;

            for (i = 1; i < rowNum; i++) {
                // Remove all rows except the first
                $rows[i].remove();
            }
            // First row input fields: set empty SKU and qty
            // eslint-disable-next-line jquery-no-input-event-shorthand
            $rows[0].select('input[name="sku"]')[0].value = '';
            // eslint-disable-next-line jquery-no-input-event-shorthand
            $rows[0].select('input[name="qty"]')[0].value = '';
        },

        /**
         * Add parameters for error source grid (see adminCheckout.submitConfigured() described in constructor)
         *
         * @param {Object} params
         */
        addErrorSourceGrid: function (params) {
            this.errorSourceGrid = params;
        },

        /**
         * @param {Object} event
         * @return {Boolean}
         */
        formKeyPress: function (event) {
            if (event.keyCode == Event.KEY_RETURN) { //eslint-disable-line eqeqeq
                this.submitSkuForm();
            }

            return false;
        }
    };
});
