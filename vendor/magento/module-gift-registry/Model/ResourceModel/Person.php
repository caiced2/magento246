<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftRegistry\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Gift registry entity registrants resource model
 *
 * @api
 * @since 100.0.2
 */
class Person extends AbstractDb
{
    /**
     * Resource model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftregistry_person', 'person_id');
    }

    /**
     * Serialization for custom attributes
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $object->setCustomValues($this->getSerializer()->serialize($object->getCustom()));
        return parent::_beforeSave($object);
    }

    /**
     * De-serialization for custom attributes
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $object->setCustom($this->getSerializer()->unserialize($object->getCustomValues()));
        return parent::_afterLoad($object);
    }

    /**
     * Delete orphan persons
     *
     * @param int $entityId
     * @param array $personLeft - records which should not be deleted
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function deleteOrphan($entityId, $personLeft = [])
    {
        $connection = $this->getConnection();
        $condition = [];
        $condition[] = $connection->quoteInto('entity_id = ?', (int)$entityId);
        $condition[] = $connection->quoteInto('person_id NOT IN (?)', $personLeft);
        $connection->delete($this->getMainTable(), $condition);

        return $this;
    }
}
