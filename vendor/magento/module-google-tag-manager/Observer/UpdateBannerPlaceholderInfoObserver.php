<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateBannerPlaceholderInfoObserver implements ObserverInterface
{
    /**
     * @var null|\Magento\GoogleTagManager\Block\ListJson
     */
    protected $blockPromotions = null;

    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\GoogleTagManager\Block\ListJson $blockPromotions
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\GoogleTagManager\Block\ListJson $blockPromotions
    ) {
        $this->helper = $helper;
        $this->blockPromotions = $blockPromotions;
    }

    /**
     * Add banner promotion code for Google Analytics
     *
     * Fired by controller_action_postdispatch_pagecache event
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }

        // No banners were found on the page
        if ($this->blockPromotions == null) {
            return $this;
        }

        // No activated for GA tracking banners were found
        $bannerCollection = $this->blockPromotions->getBannerCollection();
        if ($bannerCollection == null || !count($bannerCollection)) {
            return $this;
        }

        $this->blockPromotions
            ->setVariableName('updatedPromotions')
            ->setTemplate('Magento_GoogleTagManager::promotion.phtml');

        /** @var \Magento\PageCache\Controller\Block $controllerAction */
        $controllerAction = $observer->getEvent()->getControllerAction();
        $body = $controllerAction->getResponse()->getBody();
        $count = 1;
        $body = str_replace('</body>', $this->blockPromotions->toHtml() . '</body>', $body, $count);
        $controllerAction->getResponse()->setBody($body);

        return $this;
    }
}
