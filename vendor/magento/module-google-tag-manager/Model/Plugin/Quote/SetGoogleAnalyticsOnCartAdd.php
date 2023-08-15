<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin\Quote;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\GoogleTagManager\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Class \Magento\GoogleTagManager\Model\Plugin\Quote\SetGoogleAnalyticsOnCartAdd
 *
 * Intercepts data during update cart and checked need triggered the add_to_cart event.
 */
class SetGoogleAnalyticsOnCartAdd
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
     * Parses the product Qty data after update cart event.
     *
     * In cases when product qty is increased the product data sets to registry.
     *
     * @param Quote $subject
     * @param \Closure $proceed
     * @param int $itemId
     * @param DataObject $buyRequest
     * @param null|array|DataObject $params
     * @return Item Item
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

        if ($qty > $result->getQty()) {
            return $result;
        }

        $this->setItemForTriggerAddEvent(
            $this->helper,
            $this->registry,
            $result,
            $qty
        );
        return $result;
    }

    /**
     * Sets item data to registry for triggering add event.
     *
     * @param Data $helper
     * @param Registry $registry
     * @param Item $item
     * @param float|int $qty
     * @return void
     */
    private function setItemForTriggerAddEvent(
        Data $helper,
        Registry $registry,
        Item $item,
        $qty
    ) {
        if ($helper->isTagManagerAvailable()) {
            $namespace = 'GoogleTagManager_products_addtocart';
            $registry->unregister($namespace);
            $registry->register($namespace, [[
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'qty' => $item->getQty() - $qty,
            ]]);
        }
    }
}
