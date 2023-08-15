<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Plugin\Catalog\Model\Layer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class FilterList
 * @package Magento\CatalogPermissions\Model\Plugin\Catalog\Model\Layer
 */
class FilterList
{
    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var Index
     */
    private $permissionIndex;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * FilterList constructor.
     *
     * @param ConfigInterface $permissionsConfig
     * @param Index $permissionIndex
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param Registry $registry
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Index $permissionIndex,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        Registry $registry
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->permissionIndex = $permissionIndex;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
    }

    /**
     * Filters array getter plugin
     *
     * @param \Magento\Catalog\Model\Layer\FilterList $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetFilters(\Magento\Catalog\Model\Layer\FilterList $subject, array $result)
    {
        if (!$this->permissionsConfig->isEnabled()) {
            return $result;
        }
        $currentCategory = $this->registry->registry('current_category');
        if (!$currentCategory) {
            return $this->isPriceAllowedInConfig() ?
                $result :
                $this->removePriceFiltersFromList($result);
        }
        $currentCategoryId = $currentCategory->getId();
        $permissions = $this->permissionIndex->getIndexForCategory(
            $currentCategoryId,
            $this->customerSession->getCustomerGroupId(),
            $this->storeManager->getStore()->getWebsiteId()
        );
        /**
         * Remove price filters unless prices permissions are allowed for specific category
         * or no specific category permissions set and allowed in config
         */
        if (!empty($permissions[$currentCategoryId]['grant_catalog_product_price'])) {
            if ($permissions[$currentCategoryId]['grant_catalog_product_price'] == Permission::PERMISSION_ALLOW) {
                return $result;
            }
        } elseif ($this->isPriceAllowedInConfig()) {
            return $result;
        }
        return $this->removePriceFiltersFromList($result);
    }

    /**
     * Remove price filters from resulting filters array
     *
     * @param array $filtersArray
     * @return array
     */
    private function removePriceFiltersFromList(array $filtersArray)
    {
        /** @var \Magento\Catalog\Model\Layer\Filter\AbstractFilter $filter */
        foreach ($filtersArray as $key => $filter) {
            try {
                $attribute = $filter->getAttributeModel();
                if ($attribute->getAttributeCode() == ProductInterface::PRICE) {
                    unset($filtersArray[$key]);
                    // no break until all price filters will be removed
                }
            } catch (LocalizedException $e) {
            }
        }
        return $filtersArray;
    }

    /**
     * Check is price display allowed in configuration
     *
     * @return bool
     */
    private function isPriceAllowedInConfig()
    {
        if ($this->permissionsConfig->getCatalogProductPriceMode()) {
            $groups = $this->permissionsConfig->getCatalogProductPriceGroups();
            return empty($groups) || in_array($this->customerSession->getCustomerGroupId(), $groups);
        }
        return false;
    }
}
