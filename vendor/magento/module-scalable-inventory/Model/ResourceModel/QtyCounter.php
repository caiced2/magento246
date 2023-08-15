<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model\ResourceModel;

use Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\ScalableInventory\Model\Counter\ItemsBuilder;

/**
 * Class QtyCounter
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class QtyCounter implements QtyCounterInterface
{
    const TOPIC_NAME = 'inventory.counter.updated';

    /**
     * @var ItemsBuilder
     */
    private $itemsBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * QtyCounter constructor.
     *
     * @param ItemsBuilder $itemsBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(ItemsBuilder $itemsBuilder, PublisherInterface $publisher)
    {
        $this->itemsBuilder = $itemsBuilder;
        $this->publisher = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function correctItemsQty(array $items, $websiteId, $operator)
    {
        $items = $this->itemsBuilder->build($items, $websiteId, $operator);
        $this->publisher->publish(self::TOPIC_NAME, $items);
    }
}
