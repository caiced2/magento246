<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Backup;

/**
 * Item of backup
 *
 * @api
 * @since 100.0.2
 */
class Item extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('support_backup_item', 'item_id');
    }

    /**
     * Load Item By Backup ID and Type
     *
     * @param \Magento\Support\Model\Backup\AbstractItem $item
     * @param int $backupId
     * @param int $type
     * @return Item
     */
    public function loadItemByBackupIdAndType($item, $backupId, $type)
    {
        $sql = $this->getConnection()->select()
            ->from($this->getTable('support_backup_item'))
            ->where('backup_id = ?', $backupId)
            ->where('type = ?', $type);

        $result = $this->getConnection()->fetchRow($sql);
        if (is_array($result)) {
            $item->addData($result);
        }

        return $this;
    }
}
