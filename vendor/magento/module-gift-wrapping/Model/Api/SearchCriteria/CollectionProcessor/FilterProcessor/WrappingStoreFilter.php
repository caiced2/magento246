<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection\AbstractDb;

class WrappingStoreFilter implements CustomFilterInterface
{
    /**
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        /** @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection $collection */
        $storeId = (int) $filter->getValue();
        $collection->addStoreAttributesToResult($storeId);
        return true;
    }
}
