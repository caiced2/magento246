<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model;

class ItemManager
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Copy item to given wishlist
     *
     * @param \Magento\Wishlist\Model\Item $item
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param int $qty
     * @return void
     * @throws \InvalidArgumentException|\DomainException
     */
    public function copy(
        \Magento\Wishlist\Model\Item $item,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        $qty = null
    ) {
        if (!$item->getId()) {
            throw new \InvalidArgumentException();
        }
        if ($item->getWishlistId() == $wishlist->getId()) {
            throw new \DomainException();
        }
        $buyRequest = $item->getBuyRequest();
        if ($qty) {
            $buyRequest->setQty($qty);
        }
        $wishlist->addNewItem($item->getProduct(), $buyRequest);
        $this->eventManager->dispatch(
            'wishlist_add_product',
            ['wishlist' => $wishlist, 'product' => $item->getProduct(), 'item' => $item]
        );
    }

    /**
     * Move item to given wishlist.
     *
     * Check whether item belongs to one of customer's wishlists
     *
     * @param \Magento\Wishlist\Model\Item $item
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $customerWishlists
     * @param int $qty
     * @return void
     * @throws \InvalidArgumentException|\DomainException
     */
    public function move(
        \Magento\Wishlist\Model\Item $item,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $customerWishlists,
        $qty = null
    ) {
        if (!$item->getId()) {
            throw new \InvalidArgumentException();
        }
        if ($item->getWishlistId() == $wishlist->getId()) {
            throw new \DomainException('', 1);
        }
        if (!$customerWishlists->getItemById($item->getWishlistId())) {
            throw new \DomainException('', 2);
        }

        $buyRequest = $item->getBuyRequest();
        if ($qty) {
            $buyRequest->setQty($qty);
        }
        $wishlist->addNewItem($item->getProduct(), $buyRequest);
        $qtyDiff = $item->getQty() - $qty;
        if ($qty && $qtyDiff > 0) {
            $item->setQty($qtyDiff);
            $item->save();
        } else {
            $item->delete();
        }
    }
}
