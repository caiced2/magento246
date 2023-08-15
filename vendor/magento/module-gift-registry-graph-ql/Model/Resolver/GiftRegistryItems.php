<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\Item;

/**
 * Fetches the customer gift registry items
 */
class GiftRegistryItems implements ResolverInterface
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param Uid $idEncoder
     */
    public function __construct(Uid $idEncoder)
    {
        $this->idEncoder = $idEncoder;
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
            throw new GraphQlInputException(__(
                '"%1" value should be specified',
                ['model']
            ));
        }

        /** @var Entity $model */
        $model = $value['model'];
        $items = [];

        /** @var Item $item */
        foreach ($model->getItemsCollection() as $item) {
            $product = $item->getProduct();
            $product->setCustomOptions($item->getOptionsByCode());
            $items[] = [
                'uid' => $this->idEncoder->encode((string) $item->getId()),
                'quantity' => $item->getQty(),
                'quantity_fulfilled' => $item->getQtyFulfilled() ?? 0,
                'note' => $item->getNote(),
                'created_at' => $item->getAddedAt(),
                'model' => $product
            ];
        }

        return $items;
    }
}
