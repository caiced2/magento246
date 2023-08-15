<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesEventOrderToQuote implements ObserverInterface
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
     * Import giftwrapping data from order to quote
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStore()->getId();
        // Do not import giftwrapping data if order is reordered or GW is not available for order
        $giftWrappingHelper = $this->giftWrappingData;
        if ($order->getReordered() || !$giftWrappingHelper->isGiftWrappingAvailableForOrder($storeId)) {
            return $this;
        }
        $quote = $observer->getEvent()->getQuote();
        $quote->setGwId(
            $order->getGwId()
        )->setGwAllowGiftReceipt(
            $order->getGwAllowGiftReceipt()
        )->setGwAddCard(
            $order->getGwAddCard()
        );
        return $this;
    }
}
