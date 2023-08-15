<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Helper;

use Magento\Store\Model\ScopeInterface as Scope;

/**
 * Class Data
 *
 * @api
 * @since 100.0.2
 */
class Data extends \Magento\GoogleAnalytics\Helper\Data
{

    const XML_PATH_CONTAINER_ID = 'google/analytics/container_id';
    const XML_PATH_LIST_CATALOG_PAGE = 'google/analytics/catalog_page_list_value';
    const XML_PATH_LIST_CROSSSELL_BLOCK = 'google/analytics/crosssell_block_list_value';
    const XML_PATH_LIST_UPSELL_BLOCK = 'google/analytics/upsell_block_list_value';
    const XML_PATH_LIST_RELATED_BLOCK = 'google/analytics/related_block_list_value';
    const XML_PATH_LIST_SEARCH_PAGE = 'google/analytics/search_page_list_value';
    const XML_PATH_LIST_PROMOTIONS = 'google/analytics/promotions_list_value';
    const XML_PATH_TYPE = 'google/analytics/type';

    /**
     * @var Google tag manager tracking code
     */
    const TYPE_TAG_MANAGER = 'tag_manager';

    /**
     * @var Google analytics universal tracking code
     */
    const TYPE_UNIVERSAL = 'universal';

    const GOOGLE_ANALYTICS_COOKIE_NAME = 'add_to_cart';
    const GOOGLE_ANALYTICS_COOKIE_REMOVE_FROM_CART = 'remove_from_cart';

    const PRODUCT_QUANTITIES_BEFORE_ADDTOCART = 'prev_product_qty';

    /**
     * Whether GA Plus is ready to use
     *
     * @param null|string $store
     * @return bool
     */
    public function isGoogleAnalyticsAvailable($store = null)
    {
        $gapAccountId = $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT, Scope::SCOPE_STORE, $store);
        $gtmAccountId = $this->scopeConfig->getValue(self::XML_PATH_CONTAINER_ID, Scope::SCOPE_STORE, $store);
        $accountType = $this->scopeConfig->getValue(self::XML_PATH_TYPE, Scope::SCOPE_STORE, $store);
        $enabled = false;
        switch ($accountType) {
            case self::TYPE_UNIVERSAL:
                if (!empty($gapAccountId)) {
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
    public function isTagManagerAvailable($store = null)
    {
        $gtmAccountId = $this->scopeConfig->getValue(self::XML_PATH_CONTAINER_ID, Scope::SCOPE_STORE, $store);
        $accountType = $this->scopeConfig->getValue(self::XML_PATH_TYPE, Scope::SCOPE_STORE, $store);
        $enabled = ($accountType == self::TYPE_TAG_MANAGER) && !empty($gtmAccountId);
        return $enabled && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, Scope::SCOPE_STORE, $store);
    }
}
