/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    /**
     * This widget is used in setting of a flag when a file is chosen.
     */
    $.widget('mage.fileChooser', {
        options: {
            fileNameSelector: null,
            fileNameIndicatorSelector: null
        },

        /**
         * This method binds elements found in this widget.
         * @private
         */
        _bind: function () {
            var handlers = {};

            // since the first handler is dynamic, generate the object using array notation
            handlers['change ' + this.options.fileNameSelector] = '_onFileNameChanged';

            this._on(handlers);
        },

        /**
         * This method constructs a new widget.
         * @private
         */
        _create: function () {
            this._bind();
        },

        /**
         * This method determines what the flag should be based on the filename being specified.
         * @private
         */
        _onFileNameChanged: function () {
            var fileSet = '0',
                fileName = this.element.find(this.options.fileNameSelector),
                fileNameIndicator;

            // if the file name has been specific, then the flag should be set to true
            if (typeof fileName != 'undefined' && fileName.val().length > 0) {
                fileSet = '1';
            }

            // set the field according to the flag
            fileNameIndicator = this.element.find(this.options.fileNameIndicatorSelector);

            if (fileNameIndicator.length > 0) {
                fileNameIndicator.val(fileSet);
            }
        }
    });

    return $.mage.fileChooser;
});
