<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\ObserverInterface;

class DeleteProduct implements ObserverInterface
{
    /**
     * @var \Magento\GiftRegistry\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\GiftRegistry\Model\Item\OptionFactory
     */
    protected $optionFactory;

    /**
     * @param \Magento\GiftRegistry\Model\ItemFactory $itemFactory
     * @param \Magento\GiftRegistry\Model\Item\OptionFactory $optionFactory
     */
    public function __construct(
        \Magento\GiftRegistry\Model\ItemFactory $itemFactory,
        \Magento\GiftRegistry\Model\Item\OptionFactory $optionFactory
    ) {
        $this->itemFactory = $itemFactory;
        $this->optionFactory = $optionFactory;
    }

    /**
     * Clean up gift registry items that belongs to the product.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getProduct();

        if ($product->getParentId()) {
            $productId = $product->getParentId();
        } else {
            $productId = $product->getId();
        }

        /** @var $grItem Item */
        $grItem = $this->itemFactory->create();
        /** @var $collection \Magento\GiftRegistry\Model\ResourceModel\Item\Collection */
        $collection = $grItem->getCollection()->addProductFilter($productId);

        foreach ($collection->getItems() as $item) {
            $item->delete();
        }

        /** @var $options \Magento\GiftRegistry\Model\Item\Option*/
        $options = $this->optionFactory->create();
        $optionCollection = $options->getCollection()->addProductFilter($productId);

        $itemsArray = [];
        foreach ($optionCollection->getItems() as $optionItem) {
            $itemsArray[$optionItem->getItemId()] = $optionItem->getItemId();
        }

        $collection = $grItem->getCollection()->addItemFilter(array_keys($itemsArray));

        foreach ($collection->getItems() as $item) {
            $item->delete();
        }

        return $this;
    }
}
