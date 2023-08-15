<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\App;

/**
 * Interface for global configs
 *
 * @api
 * @since 100.0.2
 */
interface ConfigInterface
{
    /**
     * Configuration path to check whether permissions are enabled
     */
    const XML_PATH_ENABLED = 'catalog/magento_catalogpermissions/enabled';

    /**
     * Configuration path for category browsing mode
     */
    const XML_PATH_GRANT_CATALOG_CATEGORY_VIEW = 'catalog/magento_catalogpermissions/grant_catalog_category_view';

    /**
     * Configuration path for display products mode
     */
    const XML_PATH_GRANT_CATALOG_PRODUCT_PRICE = 'catalog/magento_catalogpermissions/grant_catalog_product_price';

    /**
     * Configuration path for adding to cart mode
     */
    const XML_PATH_GRANT_CHECKOUT_ITEMS = 'catalog/magento_catalogpermissions/grant_checkout_items';

    /**
     * Configuration path for catalog search prohibited groups
     */
    const XML_PATH_DENY_CATALOG_SEARCH = 'catalog/magento_catalogpermissions/deny_catalog_search';

    /**
     * Configuration path for restricted landing page
     */
    const XML_PATH_LANDING_PAGE = 'catalog/magento_catalogpermissions/restricted_landing_page';

    /**#@+
     * Grant modes
     */
    const GRANT_ALL = 1;

    const GRANT_CUSTOMER_GROUP = 2;

    const GRANT_NONE = 0;

    /**#@-*/

    /**
     * Check, whether permissions are enabled
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Return category browsing mode
     *
     * @return string
     */
    public function getCatalogCategoryViewMode();

    /**
     * Return category browsing groups
     *
     * @return string[]
     */
    public function getCatalogCategoryViewGroups();

    /**
     * Return display products mode
     *
     * @return string
     */
    public function getCatalogProductPriceMode();

    /**
     * Return display products groups
     *
     * @return string[]
     */
    public function getCatalogProductPriceGroups();

    /**
     * Return adding to cart mode
     *
     * @return string
     */
    public function getCheckoutItemsMode();

    /**
     * Return adding to cart groups
     *
     * @return string[]
     */
    public function getCheckoutItemsGroups();

    /**
     * Return catalog search prohibited groups
     *
     * @return string[]
     */
    public function getCatalogSearchDenyGroups();

    /**
     * Return restricted landing page
     *
     * @return string
     */
    public function getRestrictedLandingPage();
}
