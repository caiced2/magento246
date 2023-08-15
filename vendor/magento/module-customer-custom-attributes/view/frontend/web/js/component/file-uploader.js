/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/element/file-uploader'
], function ($, Element) {
    'use strict';

    return Element.extend({

        /**
         * Handler of the file upload complete event.
         *
         * @param {Event} e
         * @param {Object} data
         */
        onFileUploaded: function (e, data) {
            var textInput = $('input[name="' + e.target.name + '_uploaded"]'),
                filePath = data.result.file;

            this._super(e, data);
            textInput.val(filePath);
        },

        /**
         * Removes provided file from thes files list.
         *
         * @param {Object} file
         * @returns {FileUploader} Chainable.
         */
        removeFile: function (file) {
            var deleteAttributeValue = $('input[name="delete_attribute_value"]').val();

            if (!this.validation.required) {
                if (deleteAttributeValue === '') {
                    $('input[name="delete_attribute_value"]').val(deleteAttributeValue + this.name);
                } else {
                    $('input[name="delete_attribute_value"]').val(deleteAttributeValue + ',' + this.name);
                }
            }

            this.value.remove(file);

            return this;
        }
    });
});
