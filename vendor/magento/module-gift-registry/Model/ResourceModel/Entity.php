<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\ResourceModel;

/**
 * Gift registry entity resource model
 *
 * @api
 * @since 100.0.2
 */
class Entity extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Event table name
     *
     * @var string
     */
    protected $_eventTable;

    /**
     * Assigning eventTable
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftregistry_entity', 'entity_id');
        $this->_eventTable = $this->getTable('magento_giftregistry_data');
    }

    /**
     * Converting some data to internal database format
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $customValues = $object->getCustomValues();
        $object->setCustomValues($this->getSerializer()->serialize($customValues));
        return parent::_beforeSave($object);
    }

    /**
     * Fetching data from event table at same time as from entity table
     *
     * @param string $field
     * @param mixed $value
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $this->_joinEventData($select);

        return $select;
    }

    /**
     * Join event table to select object
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _joinEventData($select)
    {
        $joinCondition = sprintf('e.%1$s = %2$s.%1$s', $this->getIdFieldName(), $this->getMainTable());
        $select->joinLeft(['e' => $this->_eventTable], $joinCondition, '*');
        return $select;
    }

    /**
     * Perform actions after object is loaded
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $object->setTypeById($object->getData('type_id'));
            $object->setCustomValues($this->getSerializer()->unserialize($object->getCustomValues()));
        }
        return parent::_afterLoad($object);
    }

    /**
     * Perform action after object is saved - saving data to the eventTable
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $data = [];
        foreach ($object->getStaticTypeIds() as $code) {
            $objectData = $object->getData($code);
            if ($objectData) {
                $data[$code] = $objectData;
            }
        }

        if ($object->getId()) {
            $data['entity_id'] = (int)$object->getId();
            $this->getConnection()->insertOnDuplicate($this->_eventTable, $data, array_keys($data));
        }
        return parent::_afterSave($object);
    }

    /**
     * Fetches typeId for entity
     *
     * @param int $entityId
     * @return string
     */
    public function getTypeIdByEntityId($entityId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            'type_id'
        )->where(
            $this->getIdFieldName() . ' = :entity_id'
        );
        return $this->getConnection()->fetchOne($select, [':entity_id' => $entityId]);
    }

    /**
     * Fetches websiteId for entity
     *
     * @param int $entityId
     * @return string
     */
    public function getWebsiteIdByEntityId($entityId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            'website_id'
        )->where(
            $this->getIdFieldName() . ' = :entity_id'
        );
        return $this->getConnection()->fetchOne($select, [':entity_id' => (int)$entityId]);
    }

    /**
     * Set active entity filtered by customer
     *
     * @param int $customerId
     * @param int $entityId
     * @return $this
     */
    public function setActiveEntity($customerId, $entityId)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            ['is_active' => new \Zend_Db_Expr('0')],
            ['customer_id =?' => (int)$customerId]
        );
        $connection->update(
            $this->getMainTable(),
            ['is_active' => new \Zend_Db_Expr('1')],
            ['customer_id =?' => (int)$customerId, 'entity_id = ?' => (int)$entityId]
        );
        return $this;
    }

    /**
     * Load entity by gift registry item id
     *
     * @param \Magento\GiftRegistry\Model\Entity $object
     * @param int $itemId
     * @return $this
     */
    public function loadByEntityItem($object, $itemId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(['e' => $this->getMainTable()]);
        $select->joinInner(
            ['i' => $this->getTable('magento_giftregistry_item')],
            'e.entity_id = i.entity_id AND i.item_id = :item_id',
            []
        );

        $data = $connection->fetchRow($select, [':item_id' => (int)$itemId]);
        if ($data) {
            $object->setData($data);
            $this->_afterLoad($object);
        }
        return $this;
    }

    /**
     * Load entity by url key
     *
     * @param \Magento\GiftRegistry\Model\Entity $object
     * @param string $urlKey
     * @return $this
     */
    public function loadByUrlKey($object, $urlKey)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable())->where('url_key = :url_key');

        $this->_joinEventData($select);

        $data = $connection->fetchRow($select, [':url_key' => $urlKey]);
        if ($data) {
            $object->setData($data);
            $this->_afterLoad($object);
        }

        return $this;
    }
}
