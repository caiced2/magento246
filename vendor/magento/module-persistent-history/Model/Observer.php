<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Model;

class Observer
{
    /**
     * Persistent data
     *
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $_ePersistentData = null;

    /**
     * Persistent session
     *
     * @var \Magento\Persistent\Helper\Session
     */
    protected $_persistentSession = null;

    /**
     * @param \Magento\Persistent\Helper\Session $persistentSession
     * @param \Magento\PersistentHistory\Helper\Data $ePersistentData
     */
    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\PersistentHistory\Helper\Data $ePersistentData
    ) {
        $this->_persistentSession = $persistentSession;
        $this->_ePersistentData = $ePersistentData;
    }

    /**
     * Set persistent orders to recently orders block
     *
     * @param \Magento\Sales\Block\Reorder\Sidebar $block
     * @return void
     */
    public function initReorderSidebar(\Magento\Sales\Block\Reorder\Sidebar $block)
    {
        if (!$this->_ePersistentData->isOrderedItemsPersist()) {
            return;
        }
        $block->setCustomerId($this->_getCustomerId());
    }

    /**
     * Emulate 'viewed products' block with persistent data
     *
     * @param \Magento\Reports\Block\Product\Viewed $block
     * @return void
     */
    public function emulateViewedProductsBlock(\Magento\Reports\Block\Product\Viewed $block)
    {
        if (!$this->_ePersistentData->isViewedProductsPersist()) {
            return;
        }
        $customerId = $this->_getCustomerId();
        $block->getModel()->setCustomerId($customerId)->calculate();
        $block->setCustomerId($customerId);
    }

    /**
     * Emulate 'compared products' block with persistent data
     *
     * @param \Magento\Reports\Block\Product\Compared $block
     * @return void
     */
    public function emulateComparedProductsBlock(\Magento\Reports\Block\Product\Compared $block)
    {
        if (!$this->_ePersistentData->isComparedProductsPersist()) {
            return;
        }
        $customerId = $this->_getCustomerId();
        $block->setCustomerId($customerId);
        $block->getModel()->setCustomerId($customerId)->calculate();
    }

    /**
     * Emulate 'compare products list' block with persistent data
     *
     * @param \Magento\Catalog\Block\Product\Compare\ListCompare $block
     * @return void
     */
    public function emulateCompareProductsListBlock(\Magento\Catalog\Block\Product\Compare\ListCompare $block)
    {
        if (!$this->_ePersistentData->isCompareProductsPersist()) {
            return;
        }
        $block->setCustomerId($this->_getCustomerId());
    }

    /**
     * Return persistent customer id
     *
     * @return int
     */
    protected function _getCustomerId()
    {
        return $this->_persistentSession->getSession()->getCustomerId();
    }
}
