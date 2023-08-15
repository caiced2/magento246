<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\App;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Global configs
 */
class Config implements ConfigInterface
{
    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check, whether permissions are enabled
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            ConfigInterface::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return category browsing mode
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getCatalogCategoryViewMode($store = null)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return category browsing groups
     *
     * @param null|string|bool|int|Store $store
     * @return string[]
     */
    public function getCatalogCategoryViewGroups($store = null)
    {
        $groups = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return $this->convertToArray((string)$groups);
    }

    /**
     * Return display products mode
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getCatalogProductPriceMode($store = null)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return display products groups
     *
     * @param null|string|bool|int|Store $store
     * @return string[]
     */
    public function getCatalogProductPriceGroups($store = null)
    {
        $groups = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $this->convertToArray((string)$groups);
    }

    /**
     * Return adding to cart mode
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getCheckoutItemsMode($store = null)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return adding to cart groups
     *
     * @param null|string|bool|int|Store $store
     * @return string[]
     */
    public function getCheckoutItemsGroups($store = null)
    {
        $groups = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $this->convertToArray((string)$groups);
    }

    /**
     * Return catalog search prohibited groups
     *
     * @param null|string|bool|int|Store $store
     * @return string[]
     */
    public function getCatalogSearchDenyGroups($store = null)
    {
        $groups = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $this->convertToArray((string)$groups);
    }

    /**
     * Return restricted landing page
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getRestrictedLandingPage($store = null)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_LANDING_PAGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Convert string value to array
     *
     * @param string $value
     * @return array
     */
    private function convertToArray(string $value): array
    {
        return strlen($value) ? explode(',', $value) : [];
    }
}
