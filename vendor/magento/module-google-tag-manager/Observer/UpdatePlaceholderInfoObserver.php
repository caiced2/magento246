<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\GoogleTagManager\Block\ListJson;
use Magento\GoogleTagManager\Helper\Data;

class UpdatePlaceholderInfoObserver implements ObserverInterface
{
    /**
     * @var null|ListJson
     */
    protected $blockPromotions = null;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     * @param ListJson $blockPromotions
     */
    public function __construct(
        Data $helper,
        ListJson $blockPromotions
    ) {
        $this->helper = $helper;
        $this->blockPromotions = $blockPromotions;
    }

    /**
     * Fires by the render_block event of the Magento_PageCache module only
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }

        $block = $observer->getEvent()->getBlock();

        // Caching Banner Widget from FPC
        if ($block instanceof \Magento\Banner\Block\Widget\Banner) {
            $this->blockPromotions = $this->blockPromotions
                ->appendBannerBlock($block);
        }

        return $this;
    }
}
