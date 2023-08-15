<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model\ResourceModel;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class QtyCounterProxy
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class QtyCounterProxy implements QtyCounterInterface
{
    const CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE = 'cataloginventory/item_options/use_deferred_stock_update';

    /**
     * @var \Magento\ScalableInventory\Model\ResourceModel\QtyCounter
     */
    private $qtyCounter;

    /**
     * @var Stock
     */
    private $stockResource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param QtyCounter $qtyCounter
     * @param Stock $stockResource
     * @param ScopeConfigInterface $scopeConfig
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        QtyCounter $qtyCounter,
        Stock $stockResource,
        ScopeConfigInterface $scopeConfig,
        StockRegistryInterface $stockRegistry
    ) {
        $this->qtyCounter = $qtyCounter;
        $this->stockResource = $stockResource;
        $this->scopeConfig = $scopeConfig;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function correctItemsQty(array $items, $websiteId, $operator)
    {
        if ($this->isDeferredStockUpdate()) {
            $separatedItems = $this->getSeparatedItems($items, $websiteId);
            $this->qtyCounter->correctItemsQty($separatedItems['deferred_items'], $websiteId, $operator);
            $items = $separatedItems['sync_items'];
        }

        if (!empty($items)) {
            $this->stockResource->correctItemsQty($items, $websiteId, $operator);
        }
    }

    /**
     * @return bool
     */
    private function isDeferredStockUpdate()
    {
        return $this->scopeConfig->isSetFlag(Configuration::XML_PATH_BACKORDERS)
            && $this->scopeConfig->isSetFlag(self::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE);
    }

    /**
     * @param array $items
     * @param int $websiteId
     * @return int[][]
     */
    private function getSeparatedItems(array $items, $websiteId)
    {
        $separatedItems = [
            'deferred_items' => [],
            'sync_items' => [],
        ];

        foreach ($items as $productId => $qty) {
            $stockItem = $this->stockRegistry->getStockItem($productId, $websiteId);
            $useBackorders = $stockItem->getBackorders() > 0;
            $useConfigDeferredStockUpdate = (bool)$stockItem->getUseConfigDeferredStockUpdate();
            $deferredStockUpdate = (bool)$stockItem->getDeferredStockUpdate();

            if ($useBackorders && ($useConfigDeferredStockUpdate || $deferredStockUpdate)) {
                $separatedItems['deferred_items'][$productId] = $qty;
            } else {
                $separatedItems['sync_items'][$productId] = $qty;
            }
        }

        return $separatedItems;
    }
}
