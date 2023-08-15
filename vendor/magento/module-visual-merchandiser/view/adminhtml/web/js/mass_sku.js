/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global setLocation:true*/
define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.visualMerchandiserMassSku', {
        options: {
            massAssignButton: null,
            massAssignUrl: null,
            messagesContainer: '[data-role=messages]'
        },
        form: null,

        /**
         * @private
         */
        _create: function () {
            this.form = this.element.find('form');
            this._bind();
        },

        /**
         * @private
         */
        _bind: function () {
            $(this.options.massAssignButton).on('click', $.proxy(this._massAssignAction, this));
        },

        /**
         * @param {Event} event
         * @private
         */
        _massAssignAction: function (event) {
            var button = $(Event.element(event)),
                action = {
                    action: button.attr('role')
                };

            $.ajax({
                type: 'POST',
                data: this.form.serialize() + '&' + $.param(action),
                url: this.options.massAssignUrl,
                context: $('body'),
                showLoader: true,
                success: $.proxy(this._massActionSuccess, this)
            });
        },

        /**
         * @param {Object} response
         * @private
         */
        _massActionSuccess: function (response) {
            this._validateAjax(response);

            // jscs:disable requireCamelCaseOrUpperCaseIdentifiers
            this.element.find(this.options.messagesContainer).html(response.html_message);
            // jscs:enable requireCamelCaseOrUpperCaseIdentifiers

            this.element.trigger('requestReload', {
                'action': response.action, 'ids': response.ids
            });
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
        }
    });

    return $.mage.visualMerchandiserMassSku;
});
