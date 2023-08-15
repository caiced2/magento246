<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer segment data grid collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\CustomerSegment\Model\ResourceModel\Grid;

class Collection extends \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection
{
    /**
     * Add associated website IDs to each item of the collection.
     *
     * These IDs are not loaded by default.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        /** @var \Magento\CustomerSegment\Model\Segment $item */
        foreach ($this->_items as $item) {
            // Getter will lazily load website IDs so no other actions are required to initialize 'website_ids' field
            $item->getWebsiteIds();
        }
        return $this;
    }
}
