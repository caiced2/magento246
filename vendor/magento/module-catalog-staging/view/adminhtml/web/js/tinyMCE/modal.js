/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
        'jquery',
        'uiElement'
    ],
    function ($, Element) {
        'use strict';

        return Element.extend({
            defaults: {
                parentSelector: '.modals-wrapper',
                modalSelector: '.scheduled-changes-modal-slide',
                partOfElementId: 'catalogstaging'
            },

            /**
             * @inheritdoc
             */
            initialize: function () {
                var block,
                    arr,
                    wysiwyg;

                this._super();

                // The fix should be applied only if tinyMCE defined
                if (typeof tinyMCE === 'undefined') {
                    return;
                }

                /* eslint-disable no-undef */
                wysiwyg = tinyMCE;

                // The fix should be applied for tinyMCE3 only
                if (wysiwyg.majorVersion !== '3') {
                    return;
                }
                block = this;

                // remove active wysiwyg editors when modal popup closed
                $(block.parentSelector).on('modalclosed', block.modalSelector, function () {
                    if (wysiwyg.editors.length === 0) {
                        return;
                    }
                    arr = wysiwyg.editors.filter(function () {
                        return true;
                    });
                    arr.each(function (editor) {
                        if (editor.editorId.indexOf(block.partOfElementId) === 0) {
                            editor.remove();
                        }
                    });
                });
            }
        });
    }
);
