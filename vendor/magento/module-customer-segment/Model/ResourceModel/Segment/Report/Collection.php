<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\ResourceModel\Segment\Report;

class Collection extends \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection
{
    /**
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment\Report\Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addCustomerCountToSelect()->addWebsitesToResult();
        return $this;
    }
}
