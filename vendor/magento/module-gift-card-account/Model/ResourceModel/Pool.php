<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\ResourceModel;

/**
 * GiftCard pool resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Pool extends \Magento\GiftCardAccount\Model\ResourceModel\Pool\AbstractPool
{
    /**
     * Define main table and primary key field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftcardaccount_pool', 'code');
    }

    /**
     * Save some code
     *
     * @param string $code
     * @return void
     */
    public function saveCode($code)
    {
        $field = $this->getIdFieldName();
        $this->getConnection()->insert($this->getMainTable(), [$field => $code]);
    }

    /**
     * Check if code exists
     *
     * @param string $code
     * @return bool
     */
    public function exists($code)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($this->getMainTable(), $this->getIdFieldName());
        $select->where($this->getIdFieldName() . ' = :code');

        if ($connection->fetchOne($select, ['code' => $code]) === false) {
            return false;
        }
        return true;
    }
}
