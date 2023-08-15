<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GoogleGtag\Model\Config\GtagConfig;
use Magento\Store\Model\ScopeInterface as Scope;

class TagManagerConfig extends GtagConfig
{
    private const XML_PATH_CONTAINER_ID = 'google/gtag/analytics4/container_id';
    public const XML_PATH_LIST_CATALOG_PAGE = 'google/gtag/analytics4/catalog_page_list_value';
    public const XML_PATH_LIST_CROSSSELL_BLOCK = 'google/gtag/analytics4/crosssell_block_list_value';
    public const XML_PATH_LIST_UPSELL_BLOCK = 'google/gtag/analytics4/upsell_block_list_value';
    public const XML_PATH_LIST_RELATED_BLOCK = 'google/gtag/analytics4/related_block_list_value';
    public const XML_PATH_LIST_SEARCH_PAGE = 'google/gtag/analytics4/search_page_list_value';
    private const XML_PATH_TYPE = 'google/gtag/analytics4/type';
    private const XML_PATH_ACTIVE = 'google/gtag/analytics4/active';
    private const XML_PATH_MEASUREMENT_ID = 'google/gtag/analytics4/measurement_id';

    /**
     * @var Google tag manager tracking code
     */
    public const TYPE_TAG_MANAGER = 'tag_manager';

    /**
     * @var Google analytics4 tracking code
     */
    public const TYPE_ANALYTICS4 = 'analytics4';

    public const GOOGLE_ANALYTICS_COOKIE_NAME = 'add_to_cart';
    public const GOOGLE_ANALYTICS_COOKIE_REMOVE_FROM_CART = 'remove_from_cart';

    public const PRODUCT_QUANTITIES_BEFORE_ADDTOCART = 'prev_product_qty';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($scopeConfig);
    }

    /**
     * Get account type
     *
     * @param mixed $store
     * @return string
     */
    public function getAccountType($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TYPE,
            Scope::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get container id used in tag manager
     *
     * @param mixed $store
     * @return string
     */
    public function getContainerId($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CONTAINER_ID,
            Scope::SCOPE_STORE,
            $store
        );
    }

    /**
     * Whether GA Plus is ready to use
     *
     * @param mixed $store
     * @return bool
     */
    public function isGoogleAnalyticsAvailable($store = null): bool
    {
        $measurementId = $this->scopeConfig->getValue(
            self::XML_PATH_MEASUREMENT_ID,
            Scope::SCOPE_STORE,
            $store
        );
        $gtmAccountId = $this->scopeConfig->getValue(
            self::XML_PATH_CONTAINER_ID,
            Scope::SCOPE_STORE,
            $store
        );
        $accountType = $this->scopeConfig->getValue(self::XML_PATH_TYPE, Scope::SCOPE_STORE, $store);
        $enabled = false;
        switch ($accountType) {
            case self::TYPE_ANALYTICS4:
                if (!empty($measurementId)) {
                    $enabled = true;
                }
                break;
            case self::TYPE_TAG_MANAGER:
                if (!empty($gtmAccountId)) {
                    $enabled = true;
                }
                break;
        }
        return $enabled && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, Scope::SCOPE_STORE, $store);
    }

    /**
     * Whether GTM is ready to use
     *
     * @param mixed $store
     * @return bool
     */
    public function isTagManagerAvailable($store = null): bool
    {
        $gtmAccountId = $this->scopeConfig->getValue(self::XML_PATH_CONTAINER_ID, Scope::SCOPE_STORE, $store);
        $accountType = $this->scopeConfig->getValue(self::XML_PATH_TYPE, Scope::SCOPE_STORE, $store);
        $enabled = ($accountType == self::TYPE_TAG_MANAGER) && !empty($gtmAccountId);
        return $enabled && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, Scope::SCOPE_STORE, $store);
    }
}
