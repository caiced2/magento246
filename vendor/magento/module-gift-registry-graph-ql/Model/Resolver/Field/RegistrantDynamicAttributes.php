<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver\Field;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolves the gift registry registrant dynamic attributes
 */
class RegistrantDynamicAttributes implements ResolverInterface
{
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

        if (!isset($value['personModel'])) {
            throw new GraphQlInputException(__('"%1" value should be specified', ['personModel']));
        }

        $model = $value['model'];
        $registrant = $value['personModel'];
        $attributes = [];
        $customValues =  $registrant->unserialiseCustom()->getCustom();

        foreach ($model->getRegistrantAttributes() as $code => $metaAttribute) {
            $defaultValue = $metaAttribute['default'] ?? '';

            if ($value = $registrant->getData($code)) {
                $customValues[$code] = $value;
            }

            $attributes[] = [
                'code' => $code,
                'label' => $metaAttribute['label'],
                'value' => $customValues[$code] ?? $defaultValue
            ];
        }

        return $attributes;
    }
}
