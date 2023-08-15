<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ElasticsearchCatalogPermissions\Model\Adapter\FieldMapper\Product\FieldProvider;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProviderInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ConverterInterface
    as FieldTypeConverterInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface
    as FieldNameResolver;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;

/**
 * Provide dynamic category permissions fields for product.
 */
class CategoryPermissionsField implements FieldProviderInterface
{
    /**
     * @var FieldTypeConverterInterface
     */
    private $fieldTypeConverter;

    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;

    /**
     * @var FieldNameResolver
     */
    private $fieldNameResolver;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var GroupCollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @param FieldTypeConverterInterface $fieldTypeConverter
     * @param FieldNameResolver $fieldNameResolver
     * @param AttributeProvider $attributeAdapterProvider
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param GroupCollectionFactory $groupCollectionFactory
     */
    public function __construct(
        FieldTypeConverterInterface $fieldTypeConverter,
        FieldNameResolver $fieldNameResolver,
        AttributeProvider $attributeAdapterProvider,
        StoreCollectionFactory $storeCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory
    ) {
        $this->fieldTypeConverter = $fieldTypeConverter;
        $this->fieldNameResolver = $fieldNameResolver;
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function getFields(array $context = []): array
    {
        $allAttributes = [];
        $groups = $this->groupCollectionFactory->create()->getAllIds();
        $categoryPermissionAttribute = $this->attributeAdapterProvider->getByAttributeCode('category_permission');
        $stores = $this->storeCollectionFactory->create()->getAllIds();

        foreach ($stores as $storeId) {
            foreach ($groups as $groupId) {
                $categoryPermissionKey = $this->fieldNameResolver->getFieldName(
                    $categoryPermissionAttribute,
                    [
                        'storeId' => $storeId,
                        'customerGroupId' => $groupId,
                    ]
                );
                $allAttributes[$categoryPermissionKey] = [
                    'type' => $this->fieldTypeConverter->convert(
                        FieldTypeConverterInterface::INTERNAL_DATA_TYPE_INT
                    )
                ];
            }
        }

        return $allAttributes;
    }
}
