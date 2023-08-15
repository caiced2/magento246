<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\CartItem\DataProvider\Processor;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Index;
use Magento\CatalogPermissions\App\ConfigInterface;

/**
 * Bulk product permissions preloader for larger cart items amount.
 */
class BulkPreloader
{
    /**
     * @var Product
     */
    private $productResource;

    /**
     * @var Index
     */
    private $permissionsIndex;

    /**
     * Catalog permissions config
     *
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var array
     */
    private $loadedPermissions;

    /**
     * @param Product $productResource
     * @param Index $permissionsIndex
     * @param ConfigInterface $permissionsConfig
     */
    public function __construct(
        Product $productResource,
        Index $permissionsIndex,
        ConfigInterface $permissionsConfig
    ) {
        $this->productResource = $productResource;
        $this->permissionsIndex = $permissionsIndex;
        $this->permissionsConfig = $permissionsConfig;
    }

    /**
     * Preload product permissions data by an array of SKUs.
     *
     * @param array $skus
     * @param int $customerGroupId
     * @param int $storeId
     * @return void
     */
    public function loadBySkus(array $skus, int $customerGroupId, int $storeId): void
    {
        if (!$this->permissionsConfig->isEnabled()) {
            return;
        }
        $productIdsBySku = $this->productResource->getProductsIdsBySkus($skus);
        if (empty($productIdsBySku)) {
            return;
        }
        $permissions = $this->permissionsIndex->getIndexForProduct($productIdsBySku, $customerGroupId, $storeId);
        foreach ($productIdsBySku as $sku => $productId) {
            $this->loadedPermissions[$customerGroupId][$storeId][$sku] = $permissions[$productId] ?? [];
        }
    }

    /**
     * Get product permissions data by an array of SKUs. Returns empty array if no permissions exist.
     *
     * @param string $sku
     * @param int $customerGroupId
     * @param int $storeId
     * @return array
     */
    public function getBySku(string $sku, int $customerGroupId, int $storeId): array
    {
        if (!isset($this->loadedPermissions[$customerGroupId][$storeId][$sku])) {
            $this->loadBySkus([$sku], $customerGroupId, $storeId);
        }
        if (!isset($this->loadedPermissions[$customerGroupId][$storeId][$sku])) {
            return [];
        }
        return $this->loadedPermissions[$customerGroupId][$storeId][$sku];
    }
}
