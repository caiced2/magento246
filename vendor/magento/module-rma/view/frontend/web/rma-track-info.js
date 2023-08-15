/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'jquery-ui-modules/widget',
    'mage/decorate'
], function ($, confirm) {
    'use strict';

    $.widget('mage.rmaTrackInfo', {

        /**
         * Default options
         * @type {Object}
         */
        options: {
            addTrackNumberBtnId: '#btn-add-track-number',
            trackingCarrierSelect: '#tracking_carrier_select',
            trackingNumberInput: '#tracking_number_input',
            rmaPleaseWait: '#rma-please-wait',
            trackInfoTable: '#track-info-table',
            trackInfoTbody: '#track-info-tbody'
        },

        /**
         * Initialize and attach event callbacks for adding and deleting RMA tracking rows
         * @private
         */
        _create: function () {
            var self = this;

            self.element.trigger('mage.setUpRmaOptions', self);
            $(this.options.trackInfoTable).decorate('table');
            $(this.options.addTrackNumberBtnId).on('click', $.proxy(self._addTrackNumber, self));
            $(this.options.trackInfoTbody).on('click', 'a[data-entity-id]', function (e) {
                e.preventDefault();
                self._deleteTrackNumber.call(self, $(this).data('entity-id'));
            });
        },

        /**
         * Add new RMA tracking row
         * @private
         */
        _addTrackNumber: function () {
            if (this.element.validation().valid()) {
                $.proxy(this._poster(this.options.addLabelUrl, {
                    'carrier': $(this.options.trackingCarrierSelect).val(),
                    'number': $(this.options.trackingNumberInput).val()
                }), this);
            }
        },

        /**
         * Delete RMA tracking row for a given tracking number
         * @private
         * @param {Number} number
         */
        _deleteTrackNumber: function (number) {
            var self = this;

            confirm({
                content: this.options.deleteMsg,
                actions: {
                    /**
                     * Confirm action.
                     */
                    confirm: function () {
                        $.proxy(self._poster(self.options.deleteLabelUrl, {
                            number: number
                        }), self);
                    }
                }
            });
        },

        /**
         * Helper ajax method to post to a given url with the provided data
         * updating the markup with the return html response
         * @private
         * @param {String} url
         * @param {Object} data
         */
        _poster: function (url, data) {
            var rmaPleaseWait = $(this.options.rmaPleaseWait),
                trackInfoTbody = $(this.options.trackInfoTbody),
                trackInfoTable = $(this.options.trackInfoTable);

            $.ajax({
                url: url,
                type: 'post',
                dataType: 'html',
                cache: false,
                data: data,

                /**
                 * Before send.
                 */
                beforeSend: function () {
                    rmaPleaseWait.show();
                },

                /**
                 * @param {*} resp
                 */
                success: function (resp) {
                    trackInfoTbody.html(resp).trigger('contentUpdated');
                },

                /**
                 * Complete callback.
                 */
                complete: function () {
                    rmaPleaseWait.hide();
                    trackInfoTable.decorate('table');
                }
            });
        }
    });

    return $.mage.rmaTrackInfo;
});
