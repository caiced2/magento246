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
use Magento\GiftRegistry\Model\Entity;

/**
 * Resolves the gift registry created_at
 */
class CreatedAt implements ResolverInterface
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

        /** @var Entity $model */
        $model = $value['model'];

        if ($context->getUserId() === (int) $model->getCustomerId()) {
            return $model->getCreatedAt();
        }

        return '';
    }
}
