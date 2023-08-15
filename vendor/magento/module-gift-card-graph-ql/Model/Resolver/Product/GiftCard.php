<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardType;
use Magento\Framework\GraphQl\Query\EnumLookup;

/**
 * Post formatting for data in the giftcard type products
 *
 * {@inheritdoc}
 */
class GiftCard implements ResolverInterface
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
     * Add formatting for the giftcard product type
     *
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        $data = null;
        if ($product->getTypeId() === GiftCardType::TYPE_GIFTCARD) {
            $data = $this->enumLookup->getEnumValueFromField(
                'GiftCardTypeEnum',
                $product->getGiftcardType()
            );
        }

        return $data;
    }
}
