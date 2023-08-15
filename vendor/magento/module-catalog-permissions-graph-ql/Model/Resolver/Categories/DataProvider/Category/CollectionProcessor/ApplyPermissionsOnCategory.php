<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\Resolver\Categories\DataProvider\Category\CollectionProcessor;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider\Category\CollectionProcessorInterface;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissionsGraphQl\Model\Customer\GroupProcessor;
use Magento\CatalogPermissionsGraphQl\Model\Store\StoreProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Collection processor that accounts for permissions related to customer group
 */
class ApplyPermissionsOnCategory implements CollectionProcessorInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var Index
     */
    private $permissionIndex;

    /**
     * @var Data
     */
    private $catalogPermData;

    /**
     * @var GroupProcessor
     */
    private $groupProcessor;

    /**
     * @var StoreProcessor
     */
    private $storeProcessor;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $permissionsConfig
     * @param Index $permissionIndex
     * @param Data $catalogPermData
     * @param GroupProcessor $groupProcessor
     * @param StoreProcessor $storeProcessor
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigInterface $permissionsConfig,
        Index $permissionIndex,
        Data $catalogPermData,
        GroupProcessor $groupProcessor,
        StoreProcessor $storeProcessor
    ) {
        $this->storeManager = $storeManager;
        $this->permissionsConfig = $permissionsConfig;
        $this->permissionIndex = $permissionIndex;
        $this->catalogPermData = $catalogPermData;
        $this->groupProcessor = $groupProcessor;
        $this->storeProcessor = $storeProcessor;
    }

    /**
     * Process collection to add additional joins, attributes, and clauses to a category collection.
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

        $permissions = [];
        $categoryIds = $collection->getColumnValues('entity_id');

        if ($categoryIds) {
            $permissions = $this->permissionIndex->getIndexForCategory(
                $categoryIds,
                $customerGroupId,
                $context->getExtensionAttributes()->getStore()->getWebsiteId()
            );
        }

        foreach ($permissions as $categoryId => $permission) {
            $collection->getItemById($categoryId)->setPermissions($permission);
        }

        foreach ($collection as $key => $category) {
            $this->applyPermissionsOnCategory($category, $customerGroupId, $storeId);

            /** Filter out hidden items */
            if ($category->getIsHidden()) {
                $collection->removeItemByKey($key);
            }
        }

        return $collection;
    }

    /**
     * Apply permissions on category
     *
     * @param CategoryInterface $category
     * @param int $customerGroupId
     * @param int|null $storeId
     */
    private function applyPermissionsOnCategory(
        CategoryInterface $category,
        int $customerGroupId,
        int $storeId = null
    ): void {
        if ($category->getData('permissions/grant_catalog_category_view') == Permission::PERMISSION_DENY
            || $category->getData('permissions/grant_catalog_category_view') != Permission::PERMISSION_ALLOW
            && !$this->catalogPermData->isAllowedCategoryView($storeId, $customerGroupId)
        ) {
            $category->setIsActive(0);
            $category->setIsHidden(true);
        }
    }
}
