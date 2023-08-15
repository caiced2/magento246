<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ElasticsearchCatalogPermissionsGraphQl\Plugin\CatalogGraphQl;

use Magento\CatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;
use Magento\CatalogPermissions\App\Config as CatalogPermissionsConfig;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextFactoryInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Plugin for filtering product list based on permissions.
 */
class ProductSearchCriteriaFilter
{
    /**
     * @var CatalogPermissionsConfig
     */
    private $catalogPermissionsConfig;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var AttributeProvider
     */
    private $productAttributeProvider;

    /**
     * @var ResolverInterface
     */
    private $fieldNameResolver;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @param CatalogPermissionsConfig $catalogPermissionsConfig
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param AttributeProvider $productAttributeProvider
     * @param ResolverInterface $fieldNameResolver
     * @param ContextFactoryInterface $contextFactory
     */
    public function __construct(
        CatalogPermissionsConfig $catalogPermissionsConfig,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        AttributeProvider $productAttributeProvider,
        ResolverInterface $fieldNameResolver,
        ContextFactoryInterface $contextFactory
    ) {
        $this->catalogPermissionsConfig = $catalogPermissionsConfig;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->productAttributeProvider = $productAttributeProvider;
        $this->fieldNameResolver = $fieldNameResolver;
        $this->context = $contextFactory->create();
    }

    /**
     * Add catalog permission filter to search criteria.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $args
     * @param bool $includeAggregation
     * @return SearchCriteriaInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuild(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchCriteriaInterface $searchCriteria,
        array $args,
        bool $includeAggregation
    ): SearchCriteriaInterface {
        if ($this->catalogPermissionsConfig->isEnabled()) {
            $storeId = (int) $this->context->getExtensionAttributes()->getStore()->getId();
            $customerGroupId = (int) $this->context->getExtensionAttributes()->getCustomerGroupId();

            $categoryPermissionAttribute = $this->productAttributeProvider->getByAttributeCode('category_permission');
            $categoryPermissionField = $this->fieldNameResolver->getFieldName(
                $categoryPermissionAttribute,
                ['storeId' => $storeId, 'customerGroupId' => $customerGroupId]
            );
            $filters = [
                'category_permissions_field' => $categoryPermissionField,
                'category_permissions_value' => Permission::PERMISSION_DENY,
            ];
            foreach ($filters as $field => $value) {
                $filter = $this->filterBuilder->setField($field)
                    ->setValue($value)
                    ->create();
                $this->filterGroupBuilder->addFilter($filter);
            }
            $filterGroups = $searchCriteria->getFilterGroups();
            $filterGroups[] = $this->filterGroupBuilder->create();
            $searchCriteria->setFilterGroups($filterGroups);
        }

        return $searchCriteria;
    }
}
