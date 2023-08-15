<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Persistent helper
 */
namespace Magento\PersistentHistory\Helper;

use Magento\Framework\Module\Dir;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_PERSIST_WISHLIST = 'persistent/options/wishlist';

    const XML_PATH_PERSIST_ORDERED_ITEMS = 'persistent/options/recently_ordered';

    const XML_PATH_PERSIST_COMPARE_PRODUCTS = 'persistent/options/compare_current';

    const XML_PATH_PERSIST_COMPARED_PRODUCTS = 'persistent/options/compare_history';

    const XML_PATH_PERSIST_VIEWED_PRODUCTS = 'persistent/options/recently_viewed';

    const XML_PATH_PERSIST_CUSTOMER_AND_SEGM = 'persistent/options/customer';

    /**
     * Name of config file
     *
     * @var string
     */
    protected $_configFileName = 'persistent.xml';

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $_modulesReader;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\Dir\Reader $modulesReader
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\Dir\Reader $modulesReader
    ) {
        parent::__construct($context);
        $this->_modulesReader = $modulesReader;
    }

    /**
     * Retrieve path for config file
     *
     * @return string
     */
    public function getPersistentConfigFilePath()
    {
        return $this->_modulesReader->getModuleDir(Dir::MODULE_ETC_DIR, $this->_getModuleName())
        . '/' . $this->_configFileName;
    }

    /**
     * Check whether wishlist is persist
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isWishlistPersist($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PERSIST_WISHLIST,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether ordered items is persist
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isOrderedItemsPersist($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PERSIST_ORDERED_ITEMS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether compare products is persist
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isCompareProductsPersist($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PERSIST_COMPARE_PRODUCTS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether compared products is persist
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isComparedProductsPersist($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PERSIST_COMPARED_PRODUCTS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether viewed products is persist
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isViewedProductsPersist($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PERSIST_VIEWED_PRODUCTS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether customer and segments is persist
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isCustomerAndSegmentsPersist($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PERSIST_CUSTOMER_AND_SEGM,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
