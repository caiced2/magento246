<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin\Quote;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\GoogleTagManager\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Intercepts data during update cart and checked need triggered the remove_to_cart event.
 */
class SetGoogleAnalyticsOnCartRemove
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Data $helper
     * @param Registry $registry
     */
    public function __construct(
        Data $helper,
        Registry $registry
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
    }

    /**
     * Calls the method that sets item data to registry for triggering remove event.
     *
     * @param Quote $subject
     * @param Item $result
     * @param int $itemId
     * @return Item $result
     */
    public function afterRemoveItem(Quote $subject, $result, $itemId)
    {
        $item = $subject->getItemById($itemId);
        if ($item) {
            $this->setItemForTriggerRemoveEvent(
                $this->helper,
                $this->registry,
                $item,
                $item->getQty()
            );
        }

        return $result;
    }

    /**
     * Parses the product Qty data after update cart event.
     *
     * In cases when product qty is decreased the product data sets to registry.
     *
     * @param Quote $subject
     * @param \Closure $proceed
     * @param int $itemId
     * @param DataObject $buyRequest
     * @param null|array|DataObject $params
     * @return Item
     */
    public function aroundUpdateItem(
        Quote $subject,
        \Closure $proceed,
        $itemId,
        $buyRequest,
        $params = null
    ) {
        $item = $subject->getItemById($itemId);
        $qty = $item ? $item->getQty() : 0;
        $result = $proceed($itemId, $buyRequest, $params);

        if ($qty > $result->getQty() && (int)$itemId === (int)$result->getItemId()) {
            $this->setItemForTriggerRemoveEvent(
                $this->helper,
                $this->registry,
                $result,
                $qty - $result->getQty()
            );
        }

        return $result;
    }

    /**
     * Sets item data to registry for triggering remove event.
     *
     * @param Data $helper
     * @param Registry $registry
     * @param Item $item
     * @param int|float $qty
     * @return void
     */
    private function setItemForTriggerRemoveEvent(
        Data $helper,
        Registry $registry,
        item $item,
        $qty
    ) {
        if ($helper->isTagManagerAvailable()) {
            $namespace = 'GoogleTagManager_products_to_remove';
            $registry->unregister($namespace);
            $registry->register($namespace, [[
                'sku'   => $item->getSku(),
                'name'  => $item->getName(),
                'price' => $item->getPrice(),
                'qty'   => $qty,
            ]]);
        }
    }
}
