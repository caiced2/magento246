<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddGiftRegistryQuoteFlag implements ObserverInterface
{
    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData;

    /**
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     */
    public function __construct(\Magento\GiftRegistry\Helper\Data $giftRegistryData)
    {
        $this->_giftRegistryData = $giftRegistryData;
    }

    /**
     * Save page body to cache storage
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_giftRegistryData->isEnabled()) {
            return $this;
        }
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        $giftregistryItemId = $product->getGiftregistryItemId();
        if ($giftregistryItemId) {
            $quoteItem->setGiftregistryItemId($giftregistryItemId);

            /** @var \Magento\Quote\Model\Quote\Item $parent */
            $parent = $quoteItem->getParentItem();
            if ($parent) {
                $parent->setGiftregistryItemId($giftregistryItemId);
            }
        }
        return $this;
    }
}
