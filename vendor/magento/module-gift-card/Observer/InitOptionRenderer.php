<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Observer;

use Magento\Framework\Event\ObserverInterface;

class InitOptionRenderer implements ObserverInterface
{
    /**
     * Initialize product options renderer with giftcard specific params
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getBlock();
        $block->addOptionsRenderCfg('giftcard', \Magento\GiftCard\Helper\Catalog\Product\Configuration::class);
        return $this;
    }
}
