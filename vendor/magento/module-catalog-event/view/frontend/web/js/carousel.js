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

    $.widget('mage.carousel', {
        options: {
            panelSelector: '[data-slider-panel="slider-panel"]',
            sliderSelector: '[data-slider="slider"]',
            itemSelector: '[data-slider-item="slider-item"]',
            slideButtonSelector: '[data-slide-button="slide-button"]',
            slideButtonInactiveClass: 'inactive',
            forwardButtonClass: 'forward',
            backwardButtonClass: 'backward',
            pageSize: 6,
            scrollSize: 1,
            sliderPanelWidth: '100%',
            slideDirection: 'horizontal',
            fadeEffect: true,
            opacity: 0.5,
            duration: 1000,
            toggleInit: false
        },

        /**
         * Method binds click event
         * @private
         */
        _create: function () {
            this.items = this.element.find(this.options.itemSelector);
            this.slideConf = {};
            this.offset = 0;
            this.itemsLength = this.items.length;
            this.sliderPanel = this.element.find(this.options.panelSelector);
            this.slider = this.sliderPanel.find(this.options.sliderSelector);
            this.element.find(this.options.slideButtonSelector).on('click', $.proxy(this._handleClick, this));
            this._initializeDimensions();
            this._updateButtons();
        },

        /**
         * Method to update the forward and backward buttons
         * @private
         */
        _updateButtons: function () {
            var buttons = this.element.find(this.options.slideButtonSelector),
                button,
                elementWidth,
                totalWidth;

            this.marginLeft = this.marginTop = null;
            buttons.each($.proxy(function (key, value) {
                button = $(value);

                if (button.hasClass(this.options.backwardButtonClass)) {
                    if (this.offset <= 0) {
                        button.addClass(this.options.slideButtonInactiveClass);
                    } else {
                        button.removeClass(this.options.slideButtonInactiveClass);
                    }
                } else if (button.hasClass(this.options.forwardButtonClass)) {
                    if (this.offset >= this.itemsLength - this.options.pageSize) {
                        button.addClass(this.options.slideButtonInactiveClass);
                    } else if (this.offset < this.itemsLength - this.options.pageSize) {
                        button.removeClass(this.options.slideButtonInactiveClass);
                    } else {
                        elementWidth = this.items.first().width();
                        totalWidth = elementWidth * this.options.pageSize;

                        totalWidth > this.sliderPanel.outerWidth() ?
                            button.removeClass(this.options.slideButtonInactiveClass) :
                            button.addClass(this.options.slideButtonInactiveClass);
                    }
                }
            }, this));
        },

        /**
         * Method to update handle the click event
         * @param {event} e
         * @private
         */
        _handleClick: function (e) {
            var element = $(e.target);

            if (!element.is(this.options.slideButtonSelector)) {
                element = element.parent(this.options.slideButtonSelector);
            }

            if (!element.hasClass(this.options.slideButtonInactiveClass)) {
                if (!element.hasClass(this.options.forwardButtonClass)) {
                    this._backward();
                }

                if (!element.hasClass(this.options.backwardButtonClass)) {
                    this._forward();
                }

                this.isPlaying = true;
            }

        },

        /**
         * Method to update handle the backward button click
         * @private
         */

        _backward: function () {
            if (this.offset - this.options.scrollSize >= 0) {
                this._slide(false);
            }
        },

        /**
         * Method to update handle the forward button click
         * @private
         */

        _forward: function () {
            if (this.offset + this.options.scrollSize <= this.itemsLength - 1) {
                this._slide(true);
            }
        },

        /**
         * Method to slide
         * @param {Boolean} isForward
         * @private
         */
        _slide: function (isForward) {
            if (!this.isPlaying) {

                if (this.options.slideDirection === 'horizontal') {
                    this.slideConf['margin-left'] = this._getSlidePosition(isForward).left;
                } else {
                    this.slideConf['margin-top'] = this._getSlidePosition(isForward).top;
                }

                this._start();
            }
        },

        /**
         * Method to start slide
         * @private
         */
        _start: function () {
            if (this.options.fadeEffect) {
                this._fadeIn();
            }

            this.slider.animate(this.slideConf, $.proxy(function () {
                this.sliderPanel.fadeTo(this.options.duration, 1);
                this.isPlaying = false;
                this._updateButtons();
            }, this));
        },

        /**
         * Method to fadeIn
         * @private
         */

        _fadeIn: function () {
            this.sliderPanel.fadeTo(0, this.options.opacity);
        },

        /**
         * Method to move the slider
         *
         */
        _move: function () {
            this.slider.animate(this.slideConf, $.proxy(function () {
                this.isPlaying = false;
                this._updateButtons();
            }, this));
        },

        /**
         * Method to move the slider position
         * @private
         * @param {Boolean} isForward
         * @return {Object} itemOffset
         */

        _getSlidePosition: function (isForward) {
            var targetOffset,
                item,
                itemOffset = {
                    left: 0,
                    top: 0
                };

            if (isForward) {
                targetOffset = Math.min(this.itemsLength, this.offset + this.options.scrollSize);
            } else {
                targetOffset = Math.max(this.offset - this.options.scrollSize, 0);
            }

            this.offset = targetOffset;
            item = $(this.items[targetOffset]);

            itemOffset.left = -(item.offset().left - this.slider.offset().left + this.slider.position().left);
            itemOffset.top = -(item.offset().top - this.slider.offset().top + this.slider.position().top);

            return itemOffset;
        },

        /**
         * Method to initialize dimensions
         * @private
         */
        _initializeDimensions: function () {
            var firstItem = this.items.first(),
                offset = 0;

            if (this.options.slideDirection === 'horizontal') {
                if (this.options.toggleInit) {
                    this.sliderPanel.width(this.options.sliderPanelWidth);
                    this.items.width(this.items.first().width());
                } else {
                    offset = (window.parseInt(firstItem.css('margin-left')) +
                             window.parseInt(firstItem.css('margin-right'))) *
                             (this.options.pageSize - 1);

                    this.sliderPanel.width(this.items.first().width() * this.options.pageSize + offset + 'px');
                }
            } else {
                offset = (window.parseInt(firstItem.css('margin-bottom')) +
                         window.parseInt(firstItem.css('margin-top'))) *
                         (this.options.pageSize - 1);

                this.sliderPanel.height(this.items.first().height() * this.options.pageSize + offset + 'px');
            }

            this.sliderPanel.parent().width(this.items.first().width() * this.options.pageSize + offset + 'px');
        }
    });

    return $.mage.carousel;
});
