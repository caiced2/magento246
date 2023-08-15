<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver\Field;

use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Model\ResourceModel\Type as RegistryTypeResourceModel;
use Magento\GiftRegistry\Model\Type;
use Magento\GiftRegistry\Model\TypeFactory;

/**
 * Resolves the gift registry type dynamic attributes
 */
class TypeDynamicAttributesMetadata implements ResolverInterface
{
    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * @var RegistryTypeResourceModel
     */
    private $registryTypeResourceModel;

    /**
     * @param EnumLookup $enumLookup
     * @param TypeFactory $typeFactory
     * @param RegistryTypeResourceModel $registryTypeResourceModel
     */
    public function __construct(
        EnumLookup $enumLookup,
        TypeFactory $typeFactory,
        RegistryTypeResourceModel $registryTypeResourceModel
    ) {
        $this->enumLookup = $enumLookup;
        $this->typeFactory = $typeFactory;
        $this->registryTypeResourceModel = $registryTypeResourceModel;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['typeId'])) {
            throw new GraphQlInputException(__(
                '"%1" value should be specified',
                ['typeId']
            ));
        }

        /** @var Type $typeModel */
        $typeModel = $this->typeFactory->create();
        $this->registryTypeResourceModel->load($typeModel, $value['typeId']);
        $attributes = [];

        foreach ($typeModel->getAttributes() as $attributeGroups) {
            foreach ($attributeGroups as $code => $attribute) {
                $attributes[] = [
                    'code' => $code,
                    'input_type' => $attribute['type'],
                    'attribute_group' => $this->getGroupName($attribute['group']),
                    'label' => $attribute['label'],
                    'is_required' => (bool) $attribute['frontend']['is_required'],
                    'sort_order' => (int) $attribute['sort_order']
                ];
            }
        }

        return $attributes;
    }

    /**
     * Get attribute group name
     *
     * @param string $group
     *
     * @return string
     *
     * @throws RuntimeException
     */
    private function getGroupName(string $group): string
    {
        return $this->enumLookup->getEnumValueFromField(
            DynamicAttributes::DYNAMIC_ATTRIBUTE_GROUP,
            $group
        );
    }
}
