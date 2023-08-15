<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory as AInventory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

/**
 * Data provider for advanced inventory form
 * Add data for deferred_stock_update field
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class ScalableInventory extends AbstractModifier
{
    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param LocatorInterface $locator
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        LocatorInterface $locator,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->locator = $locator;
        $this->stockRegistry = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $model = $this->locator->getProduct();
        $modelId = $model->getId();

        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $modelId,
            $model->getStore()->getWebsiteId()
        );

        $deferredStockUpdate = $stockItem->getDeferredStockUpdate();
        if (null === $deferredStockUpdate) {
            $deferredStockUpdate = $this->stockConfiguration->getDefaultConfigValue('deferred_stock_update');
        }

        $useConfigDeferredStockUpdate = $stockItem->getUseConfigDeferredStockUpdate();
        if (null === $useConfigDeferredStockUpdate) {
            $useConfigDeferredStockUpdate
                = $this->stockConfiguration->getDefaultConfigValue('use_config_deferred_stock_update');
        }

        $data[$modelId][self::DATA_SOURCE_DEFAULT][AInventory::STOCK_DATA_FIELDS]['deferred_stock_update']
            = $deferredStockUpdate;
        $data[$modelId][self::DATA_SOURCE_DEFAULT][AInventory::STOCK_DATA_FIELDS]['use_config_deferred_stock_update']
            = $useConfigDeferredStockUpdate;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
