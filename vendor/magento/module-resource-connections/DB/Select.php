<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ResourceConnections\DB;

/**
 * Class Select
 * @package Magento\ResourceConnections\DB
 */
class Select extends \Magento\Framework\DB\Select
{
    /**
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag Whether or not the SELECT is FOR UPDATE (default true).
     * @return \Magento\Framework\DB\Select
     */
    public function forUpdate($flag = true)
    {
        $this->_adapter->setUseMasterConnection();
        return parent::forUpdate($flag);
    }
}
