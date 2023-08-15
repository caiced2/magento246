<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ViewBlockAbstractToHtmlBeforeObserver implements ObserverInterface
{
    /**
     * @var ObserverData
     */
    protected $observerData;

    /**
     * @param ObserverData $observerData
     * @param array $data
     */
    public function __construct(
        ObserverData $observerData,
        array $data = []
    ) {
        $this->observerData = $observerData;
        if (isset($data['can_edit_product_price']) && false === $data['can_edit_product_price']) {
            $this->observerData->setCanEditProductPrice(false);
        }
    }

    /**
     * Handle view_block_abstract_to_html_before event
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $block \Magento\Framework\View\Element\AbstractBlock */
        $block = $observer->getBlock();
        $blockNameInLayout = $block->getNameInLayout();
        switch ($blockNameInLayout) {
            // Handle Msrp functionality for bundle products
            case 'adminhtml.catalog.product.edit.tab.attributes':
                if (!$this->observerData->isCanEditProductPrice()) {
                    $block->setCanEditPrice(false);
                }
                break;
        }
    }
}
