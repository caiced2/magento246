<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogPermissions\Model\Permission\Index;

/**
 * Get catalog permissions for products
 */
class LoadPermissionsForProductCollection
{
    /**
     * @var Index
     */
    private $permissionIndex;
    /**
     * @var Data
     */
    private $catalogHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Index $permissionIndex
     * @param Data $catalogHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Index $permissionIndex,
        Data $catalogHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->permissionIndex = $permissionIndex;
        $this->catalogHelper = $catalogHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Get catalog permissions per product ID
     *
     * @param Collection $collection
     * @param int $customerGroupId
     * @param int $storeId
     * @return array
     */
    public function execute(Collection $collection, int $customerGroupId, int $storeId): array
    {
        $permissions = [];
        $productIds = array_keys($collection->getItems());
        $results = $this->permissionIndex->getIndexForProduct(
            $productIds,
            $customerGroupId,
            $storeId
        );
        foreach ($results as $permission) {
            $permissions[$permission['product_id']] = [
                'grant_catalog_category_view' => $permission['grant_catalog_category_view'],
                'grant_catalog_product_price' => $permission['grant_catalog_product_price'],
                'grant_checkout_items' => $permission['grant_checkout_items'],
            ];
        }

        $currentCategory = $this->catalogHelper->getCategory();
        if ($currentCategory) {
            $categoryPerm = null;
            $currentCategoryId = $currentCategory->getId();
            $categoryPerms = $this->permissionIndex->getIndexForCategory(
                $currentCategoryId,
                $customerGroupId,
                $this->storeManager->getStore($storeId)->getWebsiteId()
            );
            if (isset($categoryPerms[$currentCategoryId])) {
                $categoryPerm =  $categoryPerms[$currentCategoryId];
                $collection->addCategoryIds();
                /** @var Product $product */
                foreach ($collection as $product) {
                    if (in_array($currentCategoryId, $product->getCategoryIds())) {
                        $permissions[$product->getId()] = [
                            'grant_catalog_category_view' => $categoryPerm['grant_catalog_category_view'],
                            'grant_catalog_product_price' => $categoryPerm['grant_catalog_product_price'],
                            'grant_checkout_items' => $categoryPerm['grant_checkout_items'],
                        ];
                    }
                }
            }
        }

        return $permissions;
    }
}
