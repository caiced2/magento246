<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Model\ResourceModel\Order;

/**
 * Order archive collection
 */
class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * Generate select based on order grid select for getting archived order fields.
     *
     * @param \Magento\Framework\DB\Select $gridSelect
     * @return \Magento\Framework\DB\Select
     */
    public function getOrderGridArchiveSelect(\Magento\Framework\DB\Select $gridSelect)
    {
        $select = clone $gridSelect;
        $select->reset('from');
        $select->from(['main_table' => $this->getTable('magento_sales_order_grid_archive')], []);
        return $select;
    }
}
