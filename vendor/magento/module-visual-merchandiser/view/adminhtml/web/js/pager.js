/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global FORM_KEY:true*/
define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.visualMerchandiserTilePager', {
        options: {
            'gridUrl': null,
            'varNamePage': null,
            'limitControl': '[data-role=page_limiter]',
            'prevControl': '[data-role=button_previous]',
            'nextControl': '[data-role=button_next]',
            'inputControl': '[data-role=input_page]'
        },

        /**
         * @private
         */
        _create: function () {
            this.reloadParams = false;
            this.url = this.options.gridUrl;
            this._bind();
        },

        /**
         * Bind all events
         * @private
         */
        _bind: function () {
            this._bindElement($(this.options.limitControl), this.loadByElement);
            this._bindElement($(this.options.prevControl), this.setPage);
            this._bindElement($(this.options.nextControl), this.setPage);
            this._bindElement($(this.options.inputControl), this.inputPage);
        },

        /**
         * Bind per element
         * @private
         */
        _bindElement: function (element, callback) {
            if (element.is('select')) {
                element.on('change', $.proxy(callback, this));
            } else if (element.is('button')) {
                element.on('click', $.proxy(callback, this));
            } else if (element.is('input')) {
                element.on('keypress', $.proxy(callback, this));
            }
        },

        /**
         * Reload the grid view
         * @param {String} url
         */
        reload: function (url) {
            var ajaxRequest,
                ajaxSettings;

            this.reloadParams = this.reloadParams || {};
            // jscs:disable requireCamelCaseOrUpperCaseIdentifiers
            this.reloadParams.form_key = FORM_KEY;
            // jscs:enable requireCamelCaseOrUpperCaseIdentifiers
            url = url || this.url;

            ajaxSettings = {
                url: url + (url.match(new RegExp('\\?')) ? '&ajax=true' : '?ajax=true'),
                showLoader: true,
                method: 'post',
                context: this.element,
                data: this.reloadParams,
                dataType: 'html',
                success: $.proxy(this._onAjaxSuccess, this)
            };

            this.element.trigger('gridajaxsettings', ajaxSettings);
            ajaxRequest = $.ajax(ajaxSettings);
            this.element.trigger('gridajax', ajaxRequest);
        },

        /**
         * Set new page and reload
         * @param {Event} event
         */
        setPage: function (event) {
            var pageNumber = $(event.target).attr('data-value');

            this.reload(this.addVarToUrl(this.options.varNamePage, pageNumber));
        },

        /**
         * Set new page via page number and reload
         * @param {Event} event
         */
        inputPage: function (event) {
            var keyCode = event.keyCode || event.which,
                element = $(Event.element(event));

            if (keyCode === Event.KEY_RETURN) {
                this.reload(this.addVarToUrl(this.options.varNamePage, element.val()));
            }
        },

        /**
         * Use element properties to reload
         * @param {Event} event
         */
        loadByElement: function (event) {
            var element = event.target;

            if (element && element.name) {
                this.reload(this.addVarToUrl(element.name, element.value));
            }
        },

        /**
         * Success callback
         * @param {Object} data
         * @param {String} textStatus
         * @param {Object} transport
         * @private
         */
        _onAjaxSuccess: function (data, textStatus, transport) {
            var html = $('<div />').append(transport.responseText).find('>div').html();

            this.element.html(html);
            this.element.trigger('contentUpdated');
            this._bind();
        },

        /**
         * Writes an URL
         * @param {String} url
         * @param {String} varName
         * @param {String} varValue
         * @returns {String|*}
         * @private
         */
        _addVarToUrl: function (url, varName, varValue) {
            var re = new RegExp('\/(' + varName + '\/.*?\/)'),
                parts = url.split(new RegExp('\\?'));

            url = parts[0].replace(re, '/');
            url += varName + '/' + varValue + '/';

            if (parts.size() > 1) {
                url += '?' + parts[1];
            }

            return url;
        },

        /**
         * Wrapper for url writer
         * @param {String} varName
         * @param {String} varValue
         * @returns {*|String}
         */
        addVarToUrl: function (varName, varValue) {
            this.url = this._addVarToUrl(this.url, varName, varValue);

            return this.url;
        }
    });

    return $.mage.visualMerchandiserTilePager;
});
