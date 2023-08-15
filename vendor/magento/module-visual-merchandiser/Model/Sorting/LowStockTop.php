<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

use Magento\Framework\DB\Select;

/**
 * Rearrange product positions in category grid/tile view based on the stock ascending order
 */
class LowStockTop extends SortAbstract implements SortInterface
{
    const XML_PATH_MIN_STOCK_THRESHOLD = 'visualmerchandiser/options/minimum_stock_threshold';

    /**
     * Sort low stock on top for products in category
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sort(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        if (!$this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            return $collection;
        }

        $minStockThreshold = (int)$this->scopeConfig->getValue(self::XML_PATH_MIN_STOCK_THRESHOLD);

        $baseSet = clone $collection;
        $finalSet = clone $collection;

        $collection->getSelect()
            ->having('stock <= ?', $minStockThreshold)
            ->reset(Select::ORDER)
            ->order('stock ' . $collection::SORT_ORDER_ASC);

        $resultIds = [];

        $collection->load();

        foreach ($collection as $item) {
            $resultIds[] = $item->getId();
        }

        $ids = array_unique(array_merge($resultIds, $baseSet->getAllIds()));

        $finalSet->getSelect()
            ->reset(Select::ORDER)
            ->reset(Select::WHERE);

        $finalSet->addAttributeToFilter('entity_id', ['in' => $ids]);
        if (count($ids)) {
            $finalSet->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $ids) . ')'));
        }
        $finalSet->getSelect()
            ->reset(Select::ORDER)
            ->order('stock ' . $finalSet::SORT_ORDER_ASC);
        return $finalSet;
    }

    /**
     * Get label for the Filter
     *
     * @return string
     */
    public function getLabel()
    {
        return __("Move low stock to top");
    }
}
