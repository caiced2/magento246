<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\ResourceModel;

use Magento\GiftCardAccount\Model\Spi\GiftCardAccountResourceInterface;

/**
 * GiftCard account resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Giftcardaccount extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements
    GiftCardAccountResourceInterface
{
    /**
     * Define main table  and primary key field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftcardaccount', 'giftcardaccount_id');
    }

    /**
     * Get gift card account ID by specified code
     *
     * @param string $code
     * @return mixed
     */
    public function getIdByCode($code)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($this->getMainTable(), $this->getIdFieldName());
        $select->where('code = :code');

        if ($id = $connection->fetchOne($select, ['code' => $code])) {
            return $id;
        }

        return false;
    }

    /**
     * Update gift card accounts state
     *
     * @param array $ids
     * @param int $state
     * @return $this
     */
    public function updateState($ids, $state)
    {
        if (empty($ids)) {
            return $this;
        }
        $bind = ['state' => $state];
        $where[$this->getIdFieldName() . ' IN (?)'] = $ids;

        $this->getConnection()->update($this->getMainTable(), $bind, $where);
        return $this;
    }
}
