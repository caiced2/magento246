<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model\Counter;

use Magento\ScalableInventory\Api\Counter\ItemInterface;
use Magento\ScalableInventory\Api\Counter\ItemInterfaceFactory;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Api\Counter\ItemsInterfaceFactory;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class ItemsBuilder
{
    /**
     * @var ItemsInterfaceFactory
     */
    private $itemsFactory;

    /**
     * @var ItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * @param ItemsInterfaceFactory $itemsFactory
     * @param ItemInterfaceFactory $itemFactory
     */
    public function __construct(ItemsInterfaceFactory $itemsFactory, ItemInterfaceFactory $itemFactory)
    {
        $this->itemsFactory = $itemsFactory;
        $this->itemFactory = $itemFactory;
    }

    /**
     * @param float[] $items
     * @param int $websiteId
     * @param string $operator
     * @return ItemsInterface
     */
    public function build(array $items, $websiteId, $operator)
    {
        $objectItems = [];
        foreach ($items as $productId => $qty) {
            /** @var ItemInterface $item */
            $item = $this->itemFactory->create();
            $item->setProductId($productId);
            $item->setQty($qty);
            $objectItems[] = $item;
        }
        /** @var ItemsInterface $qty */
        $qty = $this->itemsFactory->create();
        $qty->setItems($objectItems);
        $qty->setWebsiteId($websiteId);
        $qty->setOperator($operator);

        return $qty;
    }
}
