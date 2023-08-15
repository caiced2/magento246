<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\ResourceModel;

/**
 * Increment resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Increment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_versionscms_increment', 'increment_id');
    }

    /**
     * Load increment counter by passed node and level
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param int $type
     * @param int $node
     * @param int $level
     * @return bool
     */
    public function loadByTypeNodeLevel(\Magento\Framework\Model\AbstractModel $object, $type, $node, $level)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getMainTable()
        )->forUpdate(
            true
        )->where(
            implode(
                ' AND ',
                [
                    'increment_type  = :increment_type',
                    'increment_node  = :increment_node',
                    'increment_level = :increment_level'
                ]
            )
        );

        $bind = [':increment_type' => $type, ':increment_node' => $node, ':increment_level' => $level];

        $data = $connection->fetchRow($select, $bind);

        if (!$data) {
            return false;
        }

        $object->setData($data);

        $this->_afterLoad($object);

        return true;
    }

    /**
     * Remove unneeded increment record.
     *
     * @param int $type
     * @param int $node
     * @param int $level
     * @return $this
     */
    public function cleanIncrementRecord($type, $node, $level)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['increment_type = ?' => $type, 'increment_node = ?' => $node, 'increment_level = ?' => $level]
        );

        return $this;
    }
}
