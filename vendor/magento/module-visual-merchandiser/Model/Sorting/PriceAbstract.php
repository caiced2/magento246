<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

use \Magento\Framework\DB\Select;

/**
 * Class PriceAbstract
 * @package Magento\VisualMerchandiser\Model\Sorting
 * @api
 * @since 100.0.2
 */
abstract class PriceAbstract extends SortAbstract implements SortInterface
{
    /**
     * @return string
     */
    abstract protected function getSortDirection();

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function sort(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left');
        $collection->getSelect()
            ->reset(Select::ORDER)
            ->order('price '.$this->getSortDirection());

        return $collection;
    }
}
