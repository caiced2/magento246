require([
    'jquery',
    'matchMedia',
    'mage/collapsible'
], function ($, mediaCheck) {
    'use strict';

    var footerAccordeon = $('.accordion-container');

    mediaCheck({
        media: '(min-width: 1025px)',
        /**
         * Switch to Desktop Version.
         */
        entry: function () {

            if (footerAccordeon.attr('data-collapsible')) {
                footerAccordeon.collapsible("activate").collapsible('destroy');
            }
        },

        /**
         * Switch to Mobile Version.
         */

        exit: function () {

            footerAccordeon.collapsible({
                active: false,
                animate: { duration: 500, easing: "easeOutCubic" },
                header: ".accordion-header",
                content: ".accordion-container",
                trigger: ".accordion-header",
                openedState: "active"
            });
        }
    })
});