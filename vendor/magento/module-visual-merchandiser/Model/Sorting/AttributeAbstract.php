<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

use \Magento\Framework\DB\Select;

abstract class AttributeAbstract extends SortAbstract implements SortInterface
{
    /**
     * @return string
     */
    abstract protected function getSortDirection();

    /**
     * @return string
     */
    abstract protected function getSortField();

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function sort(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        $collection->getSelect()->reset(Select::ORDER);
        $collection->addOrder($this->getSortField(), $this->getSortDirection());
        return $collection;
    }
}
