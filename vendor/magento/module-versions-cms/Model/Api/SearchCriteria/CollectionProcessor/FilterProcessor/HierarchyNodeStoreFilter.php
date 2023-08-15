<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;

class HierarchyNodeStoreFilter implements CustomFilterInterface
{
    /**
     * @param Filter $filter
     * @param \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $collection->addStoreFilter((int)$filter->getValue());
        return true;
    }
}
