/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Banner/js/model/banner',
    'underscore',
    'jquery',
    'Magento_Ui/js/lib/core/storage/local'
], function (Component, Banner, _, $, storage) {
    'use strict';

    /**
     * Stores banners initialized in getItems() method
     *
     * Prevent series reinitialization on consecutive calls to getItems() method
     *
     * @type {Array}
     */
    var initializedItems = [];

    /**
     * Returns next banner in series
     *
     * @param {Array} applicableBanners
     * @param {String} rotationType
     * @param {String} bannerId
     * @returns {Object}
     */
    function getBannerFromSeries(applicableBanners, rotationType, bannerId) {
        var storageKey,
            series,
            itemToDisplay,
            storeId;

        storeId = Banner.get('data')()['store_id'];
        storageKey = rotationType + '_' + bannerId + '_' + storeId;
        series = storage.get(storageKey);

        if (!series || !series.length) {
            series = applicableBanners;
        }

        itemToDisplay = series.shift();

        if (!series.length) {
            series = applicableBanners;
        }

        storage.set(storageKey, series);

        return itemToDisplay;
    }

    /**
     *
     * @param {Array} applicableBanners
     * @param {String} rotationType
     * @param {String} bannerId
     * @returns {Array}
     */
    function applyRotation(applicableBanners, rotationType, bannerId) {

        var result = applicableBanners;

        switch (rotationType) {

            case 'random':
                result = [applicableBanners[_.random(applicableBanners.length - 1)]];
                break;

            case 'shuffle':
                result = _.shuffle(applicableBanners);
                result = [getBannerFromSeries(result, rotationType, bannerId)];
                break;

            case 'series':
                result = [getBannerFromSeries(applicableBanners, rotationType, bannerId)];
                break;
        }

        return result;
    }

    /**
     * @param {Object} bannerConfig
     */
    function getItems(bannerConfig) {
        var applicableBanners = [],
            displayMode = bannerConfig.data('display-mode'),
            allowedBannerTypes = bannerConfig.data('types'),
            assignedBannerIds = bannerConfig.data('ids') + '',
            rotationType = bannerConfig.data('rotate'),
            blockId = bannerConfig.data('banner-id');

        if (!initializedItems[blockId] && !_.isEmpty(Banner.get('data')().items)) {
            applicableBanners = _.toArray(Banner.get('data')().items[displayMode]);

            if (!_.isEmpty(assignedBannerIds)) {
                assignedBannerIds = assignedBannerIds ? assignedBannerIds.split(',') : null;
                applicableBanners = _.filter(applicableBanners, function (banner) {
                    return !assignedBannerIds ? true : _.contains(assignedBannerIds, banner.id);
                });
                applicableBanners = _.sortBy(applicableBanners, function (banner) {
                    return _.indexOf(assignedBannerIds, banner.id);
                });
            }

            if (_.isEmpty(assignedBannerIds) && displayMode === 'fixed') {
                applicableBanners = [];
            }

            /**
             * In case allowedBannerTypes is empty filtering by type must not be performed
             */
            if (!_.isEmpty(allowedBannerTypes) && !_.isEmpty(applicableBanners)) {
                allowedBannerTypes = allowedBannerTypes ? allowedBannerTypes.split(',') : null;
                applicableBanners = _.filter(applicableBanners, function (banner) {
                    return !allowedBannerTypes || _.isEmpty(banner.types) ?  true : !_.isEmpty(
                        _.intersection(allowedBannerTypes, banner.types)
                    );
                });
            }

            initializedItems[blockId] = [];

            if (!_.isEmpty(applicableBanners)) {
                applicableBanners = applyRotation(applicableBanners, rotationType, blockId);
                _.each(applicableBanners, function (banner) {
                    initializedItems[blockId].push({
                        html: banner.content,
                        bannerId: banner.id
                    });
                });
            }
        }

        return initializedItems[blockId];
    }

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.banner = Banner.get('data');
        },

        /**
         * Register method after element render to have access to banner items.
         *
         * @param {HTMLElement} el
         */
        registerBanner: function (el) {
            var banner = $(el.parentElement);

            this['getItems' + banner.data('banner-id')] = getItems.bind(
                null,
                banner
            );
        }
    });
});
