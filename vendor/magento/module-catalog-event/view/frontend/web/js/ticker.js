/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.ticker', {
        options: {
            secondsInDay: 86400,
            secondsInHour: 3600,
            secondsInMinute: 60,
            msInSecond: 1000
        },

        /**
         * @private
         */
        _create: function () {
            var interval;

            interval = setInterval($.proxy(function () {
                var seconds = this._getEstimate(),
                    daySec = Math.floor(seconds / this.options.secondsInDay) * this.options.secondsInDay,
                    hourSec = Math.floor(seconds / this.options.secondsInHour) * this.options.secondsInHour,
                    minuteSec =  Math.floor(seconds / this.options.secondsInMinute) * this.options.secondsInMinute;

                this.element.find('[data-container="days"]').html(
                    this._formatNumber(Math.floor(daySec / this.options.secondsInDay))
                );
                this.element.find('[data-container="hour"]').html(
                    this._formatNumber(Math.floor((hourSec - daySec) / this.options.secondsInHour))
                );
                this.element.find('[data-container="minute"]').html(
                    this._formatNumber(Math.floor((minuteSec - hourSec) / this.options.secondsInMinute))
                );
                this.element.find('[data-container="second"]').html(this._formatNumber(seconds - minuteSec));

                if (daySec > 0) {
                    this.element.find('[data-container="days"]').parent().css('display', 'inline-block');
                    this.element.find('[data-container="hour"]').parent().css('display', 'inline-block');
                    this.element.find('[data-container="minute"]').parent().css('display', 'inline-block');
                    this.element.find('[data-container="second"]').parent().hide();
                } else {
                    this.element.find('[data-container="days"]').parent().hide();
                    this.element.find('[data-container="hour"]').parent().css('display', 'inline-block');
                    this.element.find('[data-container="minute"]').parent().css('display', 'inline-block');
                    this.element.find('[data-container="second"]').parent().css('display', 'inline-block');
                }

                if (!seconds) {
                    clearInterval(interval);
                }
            }, this), this.options.msInSecond);
        },

        /**
         * Get estimated remaining seconds
         *
         * @returns {Number}
         * @private
         */
        _getEstimate: function () {
            var userDate = new Date(),
                userDateOffset = userDate.getTimezoneOffset(),
                userDateOffsetMsc = userDateOffset * this.options.secondsInMinute * this.options.msInSecond,
                userDateUtc = new Date(userDate.getTime() + userDateOffsetMsc),
                endDate = new Date(this.options.eventEndTimeUTC * this.options.msInSecond + userDateOffsetMsc),
                result = (endDate.getTime() - userDateUtc.getTime())  / this.options.msInSecond;

            return result < 0 ? 0 : Math.round(result);
        },

        /**
         * Format number, prepend 0 for single digit number
         *
         * @param {Number} number
         * @returns {String}
         * @private
         */
        _formatNumber: function (number) {
            return number < 10 ? '0' + number.toString() : number.toString();
        }
    });

    return $.mage.ticker;
});
