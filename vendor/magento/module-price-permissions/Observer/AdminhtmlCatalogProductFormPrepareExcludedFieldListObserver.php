<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AdminhtmlCatalogProductFormPrepareExcludedFieldListObserver implements ObserverInterface
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
        if (isset($data['can_edit_product_status']) && false === $data['can_edit_product_status']) {
            $this->observerData->setCanEditProductStatus(false);
        }
    }

    /**
     * Handle adminhtml_catalog_product_form_prepare_excluded_field_list event
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $block \Magento\Catalog\Block\Adminhtml\Product\Edit\Action\Attribute\Tab_Attributes */
        $block = $observer->getEvent()->getObject();
        $excludedFieldList = [];

        if (!$this->observerData->isCanEditProductPrice()) {
            $excludedFieldList = [
                'price',
                'special_price',
                'tier_price',
                'special_from_date',
                'special_to_date',
                'cost',
                'price_type',
                'open_amount_max',
                'open_amount_min',
                'allow_open_amount',
                'giftcard_amounts',
                'msrp',
                'msrp_display_actual_price_type',
            ];
        }
        if (!$this->observerData->isCanEditProductStatus()) {
            $excludedFieldList[] = 'status';
        }

        $block->setFormExcludedFieldList(array_merge($block->getFormExcludedFieldList(), $excludedFieldList));
    }
}
