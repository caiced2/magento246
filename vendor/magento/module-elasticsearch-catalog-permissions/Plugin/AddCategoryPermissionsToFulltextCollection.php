<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ElasticsearchCatalogPermissions\Plugin;

use Magento\Customer\Model\Session;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\App\Config;

/**
 * Add category permissions filters to collection
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AddCategoryPermissionsToFulltextCollection
{
    /**
     * Flag to check that category permissions filters already added
     */
    private const PERMISSION_FILTER_ADDED_FLAG = 'permission_filter_added';

    /**
     * Customer session instance
     *
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $fieldNameResolver;

    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    /**
     * Constructor
     *
     * @param Session $customerSession
     * @param StoreManager $storeManager
     * @param ResolverInterface $fieldNameResolver
     * @param AttributeProvider $attributeAdapterProvider
     * @param Config $config
     * @param EngineResolverInterface $engineResolver
     */
    public function __construct(
        Session $customerSession,
        StoreManager $storeManager,
        ResolverInterface $fieldNameResolver,
        AttributeProvider $attributeAdapterProvider,
        Config $config,
        EngineResolverInterface $engineResolver
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->fieldNameResolver = $fieldNameResolver;
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->config = $config;
        $this->engineResolver = $engineResolver;
    }

    /**
     * Add catalog permissions before load collection
     *
     * @param Collection $productCollection
     * @param bool $printQuery
     * @param bool $logQuery
     */
    public function beforeLoad(Collection $productCollection, $printQuery = false, $logQuery = false)
    {
        if (!$productCollection->isLoaded()) {
            $this->applyPermissionFilter($productCollection);
        }

        return [$printQuery, $logQuery];
    }

    /**
     * Add catalog permissions before get faceted data
     *
     * @param Collection $productCollection
     * @param string $field
     * @return array
     * @see Collection::getFacetedData
     */
    public function beforeGetFacetedData(Collection $productCollection, $field)
    {
        $this->applyPermissionFilter($productCollection);

        return [$field];
    }

    /**
     * Add catalog permissions before get select count
     *
     * @param Collection $productCollection
     * @see Collection::getSelectCountSql
     */
    public function beforeGetSelectCountSql(Collection $productCollection)
    {
        if (!$productCollection->isLoaded()) {
            $this->applyPermissionFilter($productCollection);
        }
    }

    /**
     * Add catalog permissions to filter
     *
     * @param Collection $productCollection
     * @return void
     */
    private function applyPermissionFilter(Collection $productCollection): void
    {
        if ($this->avoidApplyPermissions($productCollection)) {
            return;
        }

        $categoryPermissionAttribute = $this->attributeAdapterProvider->getByAttributeCode('category_permission');
        $categoryPermissionKey = $this->fieldNameResolver->getFieldName(
            $categoryPermissionAttribute,
            [
                'storeId' => $this->storeManager->getStore()->getId(),
                'customerGroupId' => $this->customerSession->getCustomerGroupId(),
            ]
        );

        $productCollection->addFieldToFilter('category_permissions_field', $categoryPermissionKey);
        $productCollection->addFieldToFilter('category_permissions_value', Permission::PERMISSION_DENY);

        $productCollection->setFlag(self::PERMISSION_FILTER_ADDED_FLAG, true);
    }

    /**
     * Whether to avoid apply permission to collection
     *
     * @param Collection $productCollection
     * @return bool
     */
    private function avoidApplyPermissions(Collection $productCollection): bool
    {
        return $productCollection->getFlag(self::PERMISSION_FILTER_ADDED_FLAG) || !$this->config->isEnabled();
    }
}
