<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CheckoutStaging\Model\ResourceModel;

use Magento\CheckoutStaging\Setup\InstallSchema;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * PreviewQuota resource model
 */
class PreviewQuota extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('quote_preview', 'quote_id');
    }

    /**
     * Insert record
     *
     * @param int $id
     * @return bool
     */
    public function insert($id)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('quote_preview'))
            ->where('quote_id = ?', (int) $id);
        if (!empty($connection->fetchRow($select))) {
            return true;
        }
        return 1 === $connection->insert(
            $this->getTable('quote_preview'),
            ['quote_id' => (int) $id]
        );
    }
}
