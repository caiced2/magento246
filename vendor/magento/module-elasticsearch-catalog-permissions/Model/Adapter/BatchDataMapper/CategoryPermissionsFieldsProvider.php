<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ElasticsearchCatalogPermissions\Model\Adapter\BatchDataMapper;

use Magento\ElasticsearchCatalogPermissions\Model\ResourceModel\Index;
use Magento\AdvancedSearch\Model\Adapter\DataMapper\AdditionalFieldsProviderInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;

/**
 * Provide data mapping for category permissions fields
 */
class CategoryPermissionsFieldsProvider implements AdditionalFieldsProviderInterface
{
    /**
     * @var Index
     */
    private $resourceIndex;

    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;

    /**
     * @var ResolverInterface
     */
    private $fieldNameResolver;

    /**
     * @param Index $resourceIndex
     * @param AttributeProvider $attributeAdapterProvider
     * @param ResolverInterface $fieldNameResolver
     */
    public function __construct(
        Index $resourceIndex,
        AttributeProvider $attributeAdapterProvider,
        ResolverInterface $fieldNameResolver
    ) {
        $this->resourceIndex = $resourceIndex;
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->fieldNameResolver = $fieldNameResolver;
    }

    /**
     * @inheritdoc
     */
    public function getFields(array $productIds, $storeId)
    {
        $data = $this->resourceIndex->getProductPermissionsIndexData($productIds, $storeId);

        $fields = [];
        foreach ($productIds as $productId) {
            $fields[$productId] = $this->getCategoryPermissionsData($productId, $storeId, $data);
        }

        return $fields;
    }

    /**
     * Prepare category permissions index for product.
     *
     * @param int $productId
     * @param int $storeId
     * @param array $indexData
     * @return array
     */
    private function getCategoryPermissionsData(int $productId, int $storeId, array $indexData)
    {
        if (!isset($indexData[$productId])) {
            return [];
        }

        $result = [];
        $categoryPermissionAttribute = $this->attributeAdapterProvider->getByAttributeCode('category_permission');
        foreach ($indexData[$productId] as $groupId => $value) {
            $categoryPermissionKey = $this->fieldNameResolver->getFieldName(
                $categoryPermissionAttribute,
                [
                    'storeId' => $storeId,
                    'customerGroupId' => $groupId,
                ]
            );
            $result[$categoryPermissionKey] = $value;
        }

        return $result;
    }
}
