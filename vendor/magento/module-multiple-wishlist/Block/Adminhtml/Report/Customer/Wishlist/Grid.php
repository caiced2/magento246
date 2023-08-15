<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer wishlist item grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Block\Adminhtml\Report\Customer\Wishlist;

/**
 * @api
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid
{
    /**
     *  Prepare collection for Grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\MultipleWishlist\Model\ResourceModel\Item\Report\Collection */
        $collection = $this->getCollection();
        $collection->filterByStoreIds($this->_getStoreIds());
        return parent::_prepareCollection();
    }

    /**
     * Get allowed store ids array intersected with selected scope in store switcher
     *
     * @return  array
     */
    protected function _getStoreIds()
    {
        $storeIdsStr = $this->getRequest()->getParam('store_ids');
        $allowedStoreIds = array_keys($this->_storeManager->getStores());
        if ($storeIdsStr !== null && strlen($storeIdsStr)) {
            $storeIds = explode(',', $storeIdsStr);
            $storeIds = array_intersect($allowedStoreIds, $storeIds);
        } else {
            $storeIds = $allowedStoreIds;
        }
        $storeIds = array_values($storeIds);
        return $storeIds;
    }
}
