/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, $t, alert) {
    'use strict';

    $.widget('quickCheckout.configureCallbackUrl', {
        options: {
            url: '',
            website_id: '',
            elementId: '',
            successText: '',
            systemErrorText: '',
            alertTitle: '',
            alertContent: ''
        },

        /**
         * Bind handlers to events
         */
        _create: function () {
            this._on({
                'click': $.proxy(this._connect, this)
            });
        },

        /**
         * Update callback URL
         *
         * @private
         */
        _connect: function () {
            var self = this;

            $('#' + self.options.elementId + '_result').empty();

            $.ajax({
                url: this.options.url,
                showLoader: true,
                data: {website_id: this.options.website_id},
                headers: this.options.headers || {}
            }).done(function (response) {
                if (response['success']) {
                    $('#' + self.options.elementId + '_result').text(self.options.successText);
                } else {
                    alert({
                        title: self.options.alertTitle,
                        content: response['message']
                    });
                }
            }).fail(function () {
                $('#' + self.options.elementId + '_result').text(self.options.systemErrorText);
            });
        }
    });

    return $.mage.testConnection;
});
