<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GoogleTagManager\Block\ListJson;
use Magento\GoogleTagManager\Helper\Data;

class UpdateLinkedProductPlaceholderInfoObserver implements ObserverInterface
{
    /**
     * @var array
     */
    protected $fpcBlockPositions = [];

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
     * Processing Related and Up-Sell product Items rendering via FPC
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }
        $block = $observer->getEvent()->getBlock();
        $placeholder = $observer->getEvent()->getPlaceholder();
        $blockNames = [
            'CATALOG_PRODUCT_ITEM_RELATED' => ['catalog.product.related', 'related'],
            'CATALOG_PRODUCT_ITEM_UPSELL'  => ['product.info.upsell', 'upsell']
        ];

        $actualName = $blockNames[$placeholder->getName()];
        $blockImpressions = $this->blockPromotions
            ->setTemplate('Magento_GoogleTagManager::fpc/impression.phtml')
            ->setBlockName($actualName[0])
            ->setListType($actualName[1])
            ->setPosition($this->_getFpcBlockPositions($actualName[0]))
            ->setShowCategory(true)
            ->setFpcBlock($block);

        $transport = $observer->getEvent()->getTransport();
        $html = $transport->getHtml();
        $html .= $blockImpressions->toHtml();
        $transport->setHtml($html);

        return $this;
    }

    /**
     * Get Fpc block position
     *
     * @param string $key
     * @return int
     */
    protected function _getFpcBlockPositions($key)
    {
        if (!array_key_exists($key, $this->fpcBlockPositions)) {
            $this->fpcBlockPositions[$key] = 1;
        } else {
            $this->fpcBlockPositions[$key]++;
        }

        return $this->fpcBlockPositions[$key];
    }
}
