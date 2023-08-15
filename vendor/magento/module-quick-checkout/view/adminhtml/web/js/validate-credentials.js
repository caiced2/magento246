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
    'Magento_Ui/js/modal/alert',
    'jquery/ui'
], function ($, $t, alert) {
    'use strict';

    $.widget('quickCheckout.validateCredentials', {
        options: {
            url: '',
            elementId: '',
            successText: '',
            systemErrorText: '',
            fieldMapping: '',
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
         * Method triggers an AJAX request to check bolt api connection
         *
         * @private
         */
        _connect: function () {
            var self = this,
                params = {},
                fieldToCheck = 'success';

            $('#' + self.options.elementId + '_result').empty();

            $.each(this.options.fieldMapping, function (key, element) {
                params[key] = $('#' + element).val();
            });

            $.ajax({
                url: this.options.url,
                showLoader: true,
                data: params,
                headers: this.options.headers || {}
            }).done(function (response) {
                if (response[fieldToCheck]) {
                    $('#' + self.options.elementId + '_result').text(self.options.successText);
                } else {
                    alert({
                        title: self.options.alertTitle,
                        content: self.options.alertContent
                    });
                }
            }).fail(function () {
                $('#' + self.options.elementId + '_result').text(self.options.systemErrorText);
            });
        }
    });

    return $.mage.testConnection;
});
