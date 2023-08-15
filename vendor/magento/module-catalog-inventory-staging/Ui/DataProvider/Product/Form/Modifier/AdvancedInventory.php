<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogInventoryStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory as InventoryModifier;

class AdvancedInventory extends AbstractModifier
{
    /**
     * @var InventoryModifier
     */
    private $inventoryModifier;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @param InventoryModifier $inventoryModifier
     */
    public function __construct(InventoryModifier $inventoryModifier)
    {
        $this->inventoryModifier = $inventoryModifier;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        return $this->inventoryModifier->modifyData($data);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->inventoryModifier->modifyMeta($meta);
        $arrayManager = $this->getArrayManager();
        $quantityAndStockQtyPath = $arrayManager->findPath('quantity_and_stock_status_qty', $meta);
        $meta = $arrayManager->remove($quantityAndStockQtyPath, $meta);
        $quantityAndStockStatusPath = $arrayManager->findPath('quantity_and_stock_status', $meta);
        if ($quantityAndStockStatusPath) {
            $quantityAndStockStatusPath .= '/arguments/data/config/disabled';
            $meta = $arrayManager->set($quantityAndStockStatusPath, $meta, true);
        }

        return $meta;
    }

    /**
     * Get Array Manager
     *
     * @return \Magento\Framework\Stdlib\ArrayManager
     * @deprecated 100.1.4
     */
    private function getArrayManager()
    {
        if (!$this->arrayManager) {
            $this->arrayManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Stdlib\ArrayManager::class);
        }
        return $this->arrayManager;
    }
}
