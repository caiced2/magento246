<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveBeforeObserver implements ObserverInterface
{
    /**
     * Price permissions data
     *
     * @var \Magento\PricePermissions\Helper\Data
     */
    protected $_pricePermData = null;

    /**
     * @var ObserverData
     */
    protected $observerData;

    /**
     * @param \Magento\PricePermissions\Helper\Data $pricePermData
     * @param ObserverData $observerData
     * @param array $data
     */
    public function __construct(
        \Magento\PricePermissions\Helper\Data $pricePermData,
        ObserverData $observerData,
        array $data = []
    ) {
        $this->_pricePermData = $pricePermData;
        $this->observerData = $observerData;
        if (isset($data['can_edit_product_status']) && false === $data['can_edit_product_status']) {
            $this->observerData->setCanEditProductStatus(false);
        }
    }

    /**
     * Handle catalog_product_save_before event
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $helper \Magento\PricePermissions\Helper\Data */
        $helper = $this->_pricePermData;
        $this->observerData->setCanEditProductStatus($helper->getCanAdminEditProductStatus());

        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getDataObject();
        if ($product->isObjectNew() && !$this->observerData->isCanEditProductStatus()) {
            $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
        }
        if ($product->isObjectNew() && !$this->observerData->isCanReadProductPrice()) {
            $product->setPrice($this->observerData->getDefaultProductPriceString());
        }
    }
}
