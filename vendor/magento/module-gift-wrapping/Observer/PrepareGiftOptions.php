<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class PrepareGiftOptions
 *
 * Observer that listens "gift_options_prepare" event and allows to show gift options when
 * gift wrapping for order level is enabled.
 */
class PrepareGiftOptions implements ObserverInterface
{
    /**
     * Gift wrapping data.
     *
     * @var \Magento\GiftWrapping\Helper\Data|null
     */
    private $giftWrappingData;

    /**
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     */
    public function __construct(
        \Magento\GiftWrapping\Helper\Data $giftWrappingData
    ) {
        $this->giftWrappingData = $giftWrappingData;
    }

    /**
     * Set gift options available flag for order level.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $entity = $observer->getEvent()->getEntity();
        if ($this->giftWrappingData->isGiftWrappingAvailableForOrder() &&
            $entity->getQuote() &&
            !$entity->getQuote()->getIsVirtual()
        ) {
            $entity->setIsGiftOptionsAvailable(true);
        }
        return $this;
    }
}
