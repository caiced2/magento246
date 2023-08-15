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

/**
 * Resolves the gift registry dynamic attributes
 */
class DynamicAttributes implements ResolverInterface
{
    /**
     * Dynamic Attributes Group
     */
    const DYNAMIC_ATTRIBUTE_GROUP = 'GiftRegistryDynamicAttributeGroup';

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @param EnumLookup $enumLookup
     */
    public function __construct(EnumLookup $enumLookup)
    {
        $this->enumLookup = $enumLookup;
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
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"%1" value should be specified', ['model']));
        }

        $model = $value['model'];
        $attributes = [];

        foreach ($model->getRegistryAttributes() as $code => $metaAttribute) {
            if ($value = $model->getFieldValue($code)) {
                $attributes[] = [
                    'code' => $code,
                    'label' => $metaAttribute['label'],
                    'value' => $value,
                    'group' => $this->getGroupName($metaAttribute['group']),
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
            self::DYNAMIC_ATTRIBUTE_GROUP,
            $group
        );
    }
}
