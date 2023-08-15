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

/**
 * Resolves the gift registry status
 */
class GiftRegistryItemsUserErrorType implements ResolverInterface
{
    private const GIFT_REGISTRY_ERROR_MAP = 'GiftRegistryItemsUserErrorType';

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
        if (!isset($value['code'])) {
            throw new GraphQlInputException(__('"%1" value should be specified', ['code']));
        }

        $code = $this->enumLookup->getEnumValueFromField(
            self::GIFT_REGISTRY_ERROR_MAP,
            $value['code']
        );

        if (empty($code)) {
            throw new LocalizedException(__(
                'The giftRegistry doesn\'t have the correct mapped "%1" value.',
                'code'
            ));
        }
        return $code;
    }
}
