<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver\Field;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryDataMapper;

/**
 * Resolves the gift registry status
 */
class Status implements ResolverInterface
{
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

        /** @var Entity $model */
        $model = $value['model'];

        if ($context->getUserId() === (int) $model->getCustomerId()) {
            $status = $this->enumLookup->getEnumValueFromField(
                GiftRegistryDataMapper::GIFT_REGISTRY_STATUS_MAP,
                (string) $model->getData('is_active')
            );

            if (empty($status)) {
                throw new LocalizedException(__(
                    'The "%1" giftRegistry doesn\'t have the correct mapped "%2" value.',
                    [
                        $model->getData('label'),
                        'status'
                    ]
                ));
            }
        } else {
            throw new LocalizedException(__('The "%1" field is only available for owners.', ['status']));
        }

        return $status;
    }
}
