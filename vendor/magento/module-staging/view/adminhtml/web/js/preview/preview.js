/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'uiRegistry',
    'uiClass',
    'moment',
    'mageUtils',
    'jquery/ui',
    'mage/translate'
], function ($, _, registry, Class, moment, utils) {
    'use strict';

    var queryBuilder = {

        /**
         * Extracts value of specified 'GET' parameter
         * from a provided url string.
         *
         * @param {String} url - Url to be processed.
         * @param {String} key - Name of parameter to be extracted.
         * @returns {String|Undefined} Parameters' value.
         */
        get: function (url, key) {
            var regExp,
                value;

            key = key
                .replace(/[\[]/, '\\\[')
                .replace(/[\]]/, '\\\]');

            regExp = new RegExp('[\\?&]' + key + '=([^&#]*)');
            value  = regExp.exec(url);

            if (value) {
                return value[1];
            }
        },

        /**
         * Adds 'GET' parameter to the url.
         *
         * @param {String} url - Url to be processed.
         * @param {String} key - Name of parameter that will be added.
         * @param {*} value - Parameters' value.
         * @returns {String} Modified string.
         */
        set: function (url, key, value) {
            var hashIndex   = url.indexOf('#'),
                hasHash     = !~hashIndex,
                hash        = hasHash ? '' : url.substr(hashIndex),
                regExp      = new RegExp('([?&])' + key + '=.*?(&|$)', 'i'),
                separator;

            value = encodeURIComponent(value);
            url = hasHash ? url : url.substr(0, hashIndex);

            if (url.match(regExp)) {
                url = url.replace(regExp, '$1' + key + '=' + value + '$2');
            } else {
                separator = ~url.indexOf('?') ? '&' : '?';
                url += separator + key + '=' + value;
            }

            return url + hash;
        },

        /**
         * Removes specified 'GET' parameter from a provided url.
         *
         * @param {String} url - Url to be processed.
         * @param {String} key - Name of parameter that will be removed.
         * @returns {String} Modified string.
         */
        remove: function (url, key) {
            var urlParts = url.split('?'),
                baseUrl  = urlParts[0],
                query    = urlParts[1],
                regExp;

            if (!query) {
                return url;
            }

            query = query.split('#');

            regExp = new RegExp('&' + key + '(=[^&]*)?|^' + key + '(=[^&]*)?&?');
            query[0] = query[0].replace(regExp, '');

            if (query[0]) {
                baseUrl += '?' + query.join('#');
            }

            return baseUrl;
        }
    };

    return Class.extend({
        defaults: {
            storesProvider: 'store-views',
            selectors: {
                dateField: '#staging_date',
                previewHeader: '[data-role=preview-header]',
                iframeContainer: '[data-role=iframe-container]',
                iframe: '[data-role=preview-iframe]',
                updateBtn: '[data-role=update-button]',
                shareBtn: '[data-role=share-button]',
                shareModal: '[data-role=share-modal]',
                shareModalInput: '[data-role=share-modal-input]',
                closeBtn: '[data-role=collapsible-content-close]',
                collapsibleTitle: '[data-role=collapsible-title]',
                breadcrumbsLinks: 'div.breadcrumbs > ul > li > a'
            }
        },

        /**
         *
         * @returns {Preview} Chainable.
         */
        initialize: function () {
            this._super()
                .initDOMElements();

            this.storesProvider = registry.get(this.storesProvider);
            this.datetimeFormat = utils.normalizeDate(this.datetimeFormat);

            return this;
        },

        /**
         * Performs initial DOM elements manipulations.
         *
         * @returns {Preview} Chainable.
         */
        initDOMElements: function () {
            var selectors = this.selectors;

            this.$previewHeader = document.querySelector(selectors.previewHeader);
            this.$dateField = document.querySelector(selectors.dateField);
            this.$iframeContainer = document.querySelector(selectors.iframeContainer);
            this.$iframe = this.$iframeContainer.querySelector(selectors.iframe);
            this.initModal();

            $(selectors.updateBtn).on('click', this.update.bind(this));
            $(selectors.shareBtn).on('click', this.share.bind(this));
            $(selectors.closeBtn).on('click', this.onCloseBtnClick.bind(this));
            $(selectors.collapsibleTitle).on('click', this.onTitleClick.bind(this));

            $(window).on('resize', this.onWindowResize.bind(this));
            $(this.$iframe).on('load', this.onIFrameLoaded.bind(this));

            if (this.isIFrameLoaded()) {
                this.onIFrameLoaded();
            }

            this.updateIFrameHeight();

            return this;
        },

        /**
         * Init modal window for share button
         */
        initModal: function () {
            var selectors = this.selectors,
                context = this;

            $(selectors.shareModal).modal({
                title: $.mage.__('Share Preview Link'),

                /**
                 * Close current modal
                 */
                closeCurrentModal: function () {
                    this.closeModal();
                },

                /**
                 * Set current URL to modal input.
                 */
                opened: function () {
                    $(selectors.shareModalInput).val(context.buildUrl()).trigger('select');
                },
                buttons: [
                    {
                        'text': $.mage.__('Cancel'),
                        'class': 'action-secondary',

                        /**
                         * Close modal
                         */
                        'click': function () {
                            this.closeModal();
                        }
                    }
                ]
            });
        },

        /**
         * Establishes interceptor for AJAX requests made within iFrame.
         *
         * @returns {Preview} Chainable.
         */
        initIFrameAjaxInterceptor: function () {
            var iFrameWindow       = this.getIFrameWindow(),
                iFrameAjaxCallback = this.processIFrameAjax.bind(this),
                timer              = setInterval(function () {
                    if (typeof iFrameWindow.require === 'function') {
                        iFrameWindow.require(['jquery'], function (jQuery) {
                            jQuery.ajaxPrefilter(iFrameAjaxCallback);
                        });
                        clearInterval(timer);
                    }
                }, 100);

            return this;
        },

        updateBreadcrumbs: function () {
            var selectors = this.selectors,
                signatureParam = this.signatureParam,
                timestampParam = this.timestampParam,
                versionParam = this.versionParam,
                version = this.getPreviewVersion(),
                iFrameWindow = this.getIFrameWindow(),
                iframeUrl = this.getIFrameUrl(),
                timer = setInterval(function () {
                    var breadcrumbs = iFrameWindow.document.querySelectorAll(selectors.breadcrumbsLinks);

                    if (breadcrumbs.length) {
                        breadcrumbs.forEach(function (item) {
                            item.href = queryBuilder.set(item.href, versionParam, version);
                            item.href = queryBuilder.set(
                                item.href,
                                signatureParam,
                                queryBuilder.get(iframeUrl, signatureParam)
                            );
                            item.href = queryBuilder.set(
                                item.href,
                                timestampParam,
                                queryBuilder.get(iframeUrl, timestampParam)
                            );
                        });
                        clearInterval(timer);
                    }
                }, 100);
        },

        /**
         * Update page's url to match current configuration.
         */
        update: function () {
            window.location.href = this.buildUrl();
        },

        /**
         * Share current configuration.
         */
        share: function () {
            $(this.selectors.shareModal).modal('openModal');
        },

        /**
         * Creates pages' url based on the current configuration.
         *
         * @returns {String}
         */
        buildUrl: function () {
            var url            = this.getBaseUrl(),
                previewVersion = this.getPreviewVersion(),
                previewUrl     = this.buildPreviewUrl(),
                previewStore   = this.storesProvider.value();

            url = queryBuilder.set(url, this.previewVersionParam, previewVersion);
            url = queryBuilder.set(url, this.previewStoreParam, previewStore);
            url = queryBuilder.set(url, this.previewUrlParam, previewUrl);

            return url;
        },

        /**
         * Creates preview's url based on the current configuration.
         *
         * @returns {String}
         */
        buildPreviewUrl: function () {
            var url       = this.getIFrameUrl(),
                storeData = this.getSelectedStore();

            url = this.applyStoreToUrl(url, storeData);

            url = queryBuilder.remove(url, this.storeParam);
            url = queryBuilder.remove(url, this.versionParam);
            url = queryBuilder.remove(url, this.sidParam);
            url = queryBuilder.remove(url, this.timestampParam);
            url = queryBuilder.remove(url, this.signatureParam);

            return url;
        },

        /**
         * Applies store data to provided url.
         *
         * @param {String} url
         * @param {Object} storeData
         * @returns {String}
         */
        applyStoreToUrl: function (url, storeData) {
            var urlStore = this.getStoreOfUrl(url),
                path;

            path = urlStore ?
                url.slice(urlStore.baseUrl.length) :
                '';

            return storeData.baseUrl + path;
        },

        /**
         * Processes configuration of iFrame AJAX request.
         *
         * @param {Object} options
         */
        processIFrameAjax: function (options) {
            var url     = options.url,
                version = this.getPreviewVersion();

            url = queryBuilder.set(url, this.versionParam, version);

            options.url = url;
        },

        /**
         * Returns currently selected store object.
         *
         * @returns {Object}
         */
        getSelectedStore: function () {
            var provider = this.storesProvider,
                storeCode = provider.value();

            return provider.getOption(storeCode);
        },

        /**
         * Returns an array of available stores.
         *
         * @returns {Object}
         */
        getStores: function () {
            return this.storesProvider.indexedOptions;
        },

        /**
         * Extracts store data associated with provided url.
         *
         * @param {String} url
         * @returns {Object}
         */
        getStoreOfUrl: function (url) {
            var stores;

            stores = _.sortBy(this.getStores(), function (store) {
                return -store.baseUrl.length;
            });

            return _.find(stores, function (store) {
                return !!~url.indexOf(store.baseUrl);
            });
        },

        /**
         * Returns base url of the page.
         *
         * @returns {String}
         */
        getBaseUrl: function () {
            return this.baseUrl;
        },

        /**
         * Creates version identifier based on the selected date.
         *
         * @returns {Number}
         */
        getPreviewVersion: function () {
            var value = this.$dateField.value,
                date = moment.utc(value, this.datetimeFormat);

            date.subtract(this.timezoneOffset, 'seconds');

            return date.unix();
        },

        /**
         * Returns window object of the IFrame.
         *
         * @returns {Window}
         */
        getIFrameWindow: function () {
            var iframe = this.$iframe;

            return iframe.contentDocument.defaultView;
        },

        /**
         * Returns current url of the IFrame.
         *
         * @returns {String}
         */
        getIFrameUrl: function () {
            return this.getIFrameWindow().location.href;
        },

        /**
         * Checks if iFrame is loaded.
         *
         * @returns {Boolean}
         */
        isIFrameLoaded: function () {
            return this.getIFrameWindow().document.readyState === 'complete';
        },

        /**
         * Updates height of preview IFrame.
         *
         * @returns {Preview} Chainable.
         */
        updateIFrameHeight: function () {
            var windowHeight    = window.innerHeight,
                headerHeight    = this.$previewHeader.offsetHeight,
                containerHeight = windowHeight - headerHeight - 5;

            this.$iframeContainer.style.height = containerHeight + 'px';

            return this;
        },

        /**
         * Windows' 'resize' event handler.
         */
        onWindowResize: function () {
            this.updateIFrameHeight();
        },

        /**
         * Titles' 'click' event handler.
         *
         * @param {Event} e
         */
        onTitleClick: function (e) {
            var $elem = $(e.currentTarget);

            $elem.next('[data-role=collapsible-content]').slideToggle()
                    .siblings('[data-role=collapsible-content]:visible').slideUp()
                    .siblings(this.selectors.collapsibleTitle).removeClass('_active');

            $elem.toggleClass('_active');
        },

        /**
         * 'Close' button element 'click' event handler.
         */
        onCloseBtnClick: function (e) {
            var $elem = $(e.currentTarget);

            $elem.closest('[data-role=collapsible-content]').slideUp();

            $(this.selectors.collapsibleTitle).removeClass('_active');
        },

        /**
         * iFrame 'load' event handler.
         */
        onIFrameLoaded: function () {
            this.initIFrameAjaxInterceptor();
            this.updateBreadcrumbs();
        }
    });
});
