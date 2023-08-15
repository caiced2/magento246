<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessor;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data as CatalogPermissionsData;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Index;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\IndexFactory;
use Magento\CatalogPermissionsGraphQl\Model\Customer\GroupProcessor;
use Magento\CatalogPermissionsGraphQl\Model\Store\StoreProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

class ApplyCategoryPermissionsOnProductProcessor implements CollectionProcessorInterface
{
    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var CatalogPermissionsData
     */
    private $catalogPermissionsData;

    /**
     * @var IndexFactory
     */
    private $permissionIndexFactory;

    /**
     * @var Index
     */
    private $permissionIndex;

    /**
     * @var GroupProcessor
     */
    private $groupProcessor;

    /**
     * @var StoreProcessor
     */
    private $storeProcessor;

    /**
     * @param ConfigInterface $permissionsConfig
     * @param CatalogPermissionsData $catalogPermissionsData
     * @param IndexFactory $permissionIndexFactory
     * @param GroupProcessor $groupProcessor
     * @param StoreProcessor $storeProcessor
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        CatalogPermissionsData $catalogPermissionsData,
        IndexFactory $permissionIndexFactory,
        GroupProcessor $groupProcessor,
        StoreProcessor $storeProcessor
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->catalogPermissionsData = $catalogPermissionsData;
        $this->permissionIndexFactory = $permissionIndexFactory;
        $this->groupProcessor = $groupProcessor;
        $this->storeProcessor = $storeProcessor;
    }

    /**
     * Process collection to add additional joins, attributes, and clauses to a product collection.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ContextInterface $context = null
    ): Collection {
        if (!$this->permissionsConfig->isEnabled()) {
            return $collection;
        }

        $storeId = $this->storeProcessor->getStoreId($context);
        $customerGroupId = $this->groupProcessor->getCustomerGroup($context);
        $this->getPermissionIndex()->addIndexToProductCollection($collection, $customerGroupId);

        foreach ($collection as $key => $product) {
            $this->applyCategoryRelatedPermissionsOnProduct($product, $customerGroupId, $storeId);

            if ($product->getIsHidden()) {
                $collection->removeItemByKey($key);
            }
        }

        return $collection;
    }

    /**
     * Apply category related permissions on product
     *
     * @param ProductInterface $product
     * @param int $customerGroupId
     * @param int|null $storeId
     */
    public function applyCategoryRelatedPermissionsOnProduct(
        ProductInterface $product,
        int $customerGroupId,
        int $storeId = null
    ): void {
        $this->applyProductVisibility($product, $customerGroupId, $storeId);
        $this->applyProductCanShowPrice($product, $customerGroupId, $storeId);
        $this->applyProductDisabledToCart($product, $customerGroupId, $storeId);
    }

    /**
     * Apply product visibility flag
     *
     * @param ProductInterface $product
     * @param int $customerGroupId
     * @param int|null $storeId
     */
    private function applyProductVisibility(
        ProductInterface $product,
        int $customerGroupId,
        int $storeId = null
    ): void {
        if ($product->getData('grant_catalog_category_view') == Permission::PERMISSION_DENY
            || $product->getData('grant_catalog_category_view') != Permission::PERMISSION_ALLOW
            && !$this->catalogPermissionsData->isAllowedCategoryView($storeId, $customerGroupId)
        ) {
            $product->setIsHidden(true);
        }
    }

    /**
     * Apply product can show price flag
     *
     * @param ProductInterface $product
     * @param int $customerGroupId
     * @param int|null $storeId
     */
    private function applyProductCanShowPrice(
        ProductInterface $product,
        int $customerGroupId,
        int $storeId = null
    ): void {
        if ($product->getData('grant_catalog_product_price') == Permission::PERMISSION_DENY
            || $product->getData('grant_catalog_product_price') != Permission::PERMISSION_ALLOW
            && !$this->catalogPermissionsData->isAllowedProductPrice($storeId, $customerGroupId)
        ) {
            $product->setCanShowPrice(false);
            $product->setDisableAddToCart(true);
        }
    }

    /**
     * Apply product disabled to cart flag
     *
     * @param ProductInterface $product
     * @param int $customerGroupId
     * @param int|null $storeId
     */
    private function applyProductDisabledToCart(
        ProductInterface $product,
        int $customerGroupId,
        int $storeId = null
    ): void {
        if ($product->getData('grant_checkout_items') == Permission::PERMISSION_DENY
            || $product->getData('grant_checkout_items') != Permission::PERMISSION_ALLOW
            && !$this->catalogPermissionsData->isAllowedCheckoutItems($storeId, $customerGroupId)
        ) {
            $product->setDisableAddToCart(true);
        }
    }

    /**
     * Get permission index resource object.
     *
     * @return Index
     */
    private function getPermissionIndex() : Index
    {
        if (!$this->permissionIndex instanceof Index) {
            $this->permissionIndex = $this->permissionIndexFactory->create();
        }
        return $this->permissionIndex;
    }
}
