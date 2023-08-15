<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Backend\Block\Template;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Backend\Block\Widget\Grid;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductAttributeUpdateBeforeObserver implements ObserverInterface
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
     * Handle catalog_product_attribute_update_before event
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $block \Magento\Catalog\Block\Adminhtml\Product\Edit\Action\Attribute\Tab\Attributes */
        $attributesData = $observer->getEvent()->getAttributesData();
        $excludedAttributes = [];

        if (!$this->observerData->isCanEditProductPrice()) {
            $excludedAttributes = [
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
            $excludedAttributes[] = 'status';
        }
        foreach ($excludedAttributes as $excludedAttributeCode) {
            if (isset($attributesData[$excludedAttributeCode])) {
                unset($attributesData[$excludedAttributeCode]);
            }
        }

        $observer->getEvent()->setAttributesData($attributesData);
    }
}
