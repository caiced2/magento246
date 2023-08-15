<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesEventOrderItemToQuoteItem implements ObserverInterface
{
    /**
     * Gift wrapping data
     *
     * @var \Magento\GiftWrapping\Helper\Data|null
     */
    protected $giftWrappingData;

    /**
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     */
    public function __construct(
        \Magento\GiftWrapping\Helper\Data $giftWrappingData
    ) {
        $this->giftWrappingData = $giftWrappingData;
    }

    /**
     * Import giftwrapping data from order item to quote item
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // @var $orderItem \Magento\Sales\Model\Order\Item
        $orderItem = $observer->getEvent()->getOrderItem();
        // Do not import giftwrapping data if order is reordered or GW is not available for items
        $order = $orderItem->getOrder();
        $giftWrappingHelper = $this->giftWrappingData;
        if ($order && ($order->getReordered() || !$giftWrappingHelper->isGiftWrappingAvailableForItems(
            $order->getStore()->getId()
        ))
        ) {
            return $this;
        }
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $quoteItem->setGwId(
            $orderItem->getGwId()
        )->setGwBasePrice(
            $orderItem->getGwBasePrice()
        )->setGwPrice(
            $orderItem->getGwPrice()
        )->setGwBaseTaxAmount(
            $orderItem->getGwBaseTaxAmount()
        )->setGwTaxAmount(
            $orderItem->getGwTaxAmount()
        );
        return $this;
    }
}
