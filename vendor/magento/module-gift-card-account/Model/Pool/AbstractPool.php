<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Model\Pool;

/**
 * @api
 * @since 100.0.2
 */
abstract class AbstractPool extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_FREE = 0;

    const STATUS_USED = 1;

    /**
     * @var int|null
     */
    protected $_pool_percent_used = null;

    /**
     * @var int
     */
    protected $_pool_size = 0;

    /**
     * @var int
     */
    protected $_pool_free_size = 0;

    /**
     * Return first free code
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function shift()
    {
        $notInArray = $this->getExcludedIds();
        $collection = $this->getCollection()->addFieldToFilter('status', self::STATUS_FREE)->setPageSize(1);
        if (is_array($notInArray) && !empty($notInArray)) {
            $collection->addFieldToFilter('code', ['nin' => $notInArray]);
        }
        $collection->getSelect()->forUpdate(true);
        $items = $collection->getItems();
        if (!$items) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No codes left in the pool.'));
        }

        $item = array_shift($items);
        return $item->getId();
    }

    /**
     * Load code pool usage info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getPoolUsageInfo()
    {
        if ($this->_pool_percent_used === null) {
            $this->_pool_size = $this->getCollection()->getSize();
            $this->_pool_free_size = $this->getCollection()->addFieldToFilter('status', self::STATUS_FREE)->getSize();
            if (!$this->_pool_size) {
                $this->_pool_percent_used = 100;
            } else {
                $this->_pool_percent_used = 100 - round($this->_pool_free_size / ($this->_pool_size / 100), 2);
            }
        }

        $result = new \Magento\Framework\DataObject();
        $result->setTotal($this->_pool_size)->setFree($this->_pool_free_size)->setPercent($this->_pool_percent_used);
        return $result;
    }

    /**
     * Delete free codes from pool
     *
     * @return $this
     */
    public function cleanupFree()
    {
        $this->getResource()->cleanupByStatus(self::STATUS_FREE);
        return $this;
    }
}
