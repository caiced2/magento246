<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\ResourceModel\Pool;

/**
 * GiftCardAccount Pool Resource Model Abstract
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class AbstractPool extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Delete records in db using specified status as criteria
     *
     * @param int $status
     * @return $this
     */
    public function cleanupByStatus($status)
    {
        $where = ['status = ?' => $status];
        $this->getConnection()->delete($this->getMainTable(), $where);
        return $this;
    }
}
