<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Backup;

/**
 * Collection of backups
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Init Collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Support\Model\Backup::class, \Magento\Support\Model\ResourceModel\Backup::class);
    }

    /**
     * Get Backups with status processing
     *
     * @return Collection
     */
    public function addProcessingStatusFilter()
    {
        $this->addFieldToFilter('status', ['eq' => \Magento\Support\Model\Backup::STATUS_PROCESSING]);

        return $this;
    }

    /**
     * Filter Backups where status not failed
     *
     * @return Collection
     */
    public function removeBackupsWhereStatusFailed()
    {
        $this->addFieldToFilter('status', ['neq' => \Magento\Support\Model\Backup::STATUS_FAILED]);

        return $this;
    }
}
