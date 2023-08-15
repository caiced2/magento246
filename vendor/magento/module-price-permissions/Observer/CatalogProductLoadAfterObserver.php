<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductLoadAfterObserver implements ObserverInterface
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
        if (isset($data['can_edit_product_status']) && false === $data['can_edit_product_status']) {
            $this->observerData->setCanEditProductStatus(false);
        }

        if (isset($data['can_read_product_price']) && false === $data['can_read_product_price']) {
            $this->observerData->setCanReadProductPrice(false);
        }
    }

    /**
     * Handle catalog_product_load_after event
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getDataObject();

        if (!$this->observerData->isCanEditProductPrice()) {
            // Lock price attributes of product in order not to let administrator to change them
            $product->lockAttribute('price');
            $product->lockAttribute('special_price');
            $product->lockAttribute('tier_price');
            $product->lockAttribute('special_from_date');
            $product->lockAttribute('special_to_date');
            $product->lockAttribute('cost');
            // For bundle product
            $product->lockAttribute('price_type');
            // Gift Card attributes
            $product->lockAttribute('open_amount_max');
            $product->lockAttribute('open_amount_min');
            $product->lockAttribute('allow_open_amount');
            $product->lockAttribute('giftcard_amounts');
            // For Msrp fields
            $product->lockAttribute('msrp');
            $product->lockAttribute('msrp_display_actual_price_type');
        }
        if (!$this->observerData->isCanEditProductStatus()) {
            $product->lockAttribute('status');
        }
    }
}
