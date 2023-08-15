<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\ResourceModel\Segment\Report\Detail\Group;

class Option implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $_resourceCollection;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $groupCollection
     */
    public function __construct(\Magento\Customer\Model\ResourceModel\Group\Collection $groupCollection)
    {
        $this->_resourceCollection = $groupCollection;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_resourceCollection->addFieldToFilter(
            'customer_group_id',
            ['gt' => 0]
        )->load()->toOptionHash();
    }
}
