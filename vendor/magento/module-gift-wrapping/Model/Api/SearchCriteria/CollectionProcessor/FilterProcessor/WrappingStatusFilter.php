<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection\AbstractDb;

class WrappingStatusFilter implements CustomFilterInterface
{
    /**
     * @param Filter $filter
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection $collection
     * @return $this
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $collection->getSelect()->where('main_table.status = ?', (int)$filter->getValue());
        return true;
    }
}
