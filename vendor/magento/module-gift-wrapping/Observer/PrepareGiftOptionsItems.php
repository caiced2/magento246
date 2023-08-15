<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

class PrepareGiftOptionsItems implements ObserverInterface
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
     * Set gift options available flag for items
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $items = $observer->getEvent()->getItems();
        foreach ($items as $item) {
            $allowed = $item->getProduct()->getGiftWrappingAvailable();
            if ($this->giftWrappingData->isGiftWrappingAvailableForProduct($allowed) && !$item->getIsVirtual()) {
                $item->setIsGiftOptionsAvailable(true);
            }
        }
        return $this;
    }
}
