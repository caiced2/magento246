<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\ResourceModel;

/**
 * Gift registry type data resource model
 *
 * @api
 * @since 100.0.2
 */
class Type extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Info table name
     *
     * @var string
     */
    protected $_infoTable;

    /**
     * Label table name
     *
     * @var string
     */
    protected $_labelTable;

    /**
     * Initialization. Set main entity table name and primary key field name.
     * Set label and info tables
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftregistry_type', 'type_id');
        $this->_infoTable = $this->getTable('magento_giftregistry_type_info');
        $this->_labelTable = $this->getTable('magento_giftregistry_label');
    }

    /**
     * Add store date to registry type data
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();

        $scopeCheckExpr = $connection->getCheckSql(
            'store_id = 0',
            $connection->quote('default'),
            $connection->quote('store')
        );
        $storeIds = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
        if ($object->getStoreId()) {
            $storeIds[] = (int)$object->getStoreId();
        }
        $select = $connection->select()->from(
            $this->_infoTable,
            ['scope' => $scopeCheckExpr, 'label', 'is_listed', 'sort_order']
        )->where(
            'type_id = ?',
            (int)$object->getId()
        )->where(
            'store_id IN (?)',
            $storeIds
        );

        $data = $connection->fetchAssoc($select);

        if (isset($data['store']) && is_array($data['store'])) {
            foreach ($data['store'] as $key => $value) {
                $object->setData($key, $value !== null ? $value : $data['default'][$key]);
                $object->setData($key . '_store', $value);
            }
        } elseif (isset($data['default']) && is_array($data['default'])) {
            foreach ($data['default'] as $key => $value) {
                $object->setData($key, $value);
            }
        }

        return parent::_afterLoad($object);
    }

    /**
     * Save registry type per store view data
     *
     * @param \Magento\GiftRegistry\Model\Type $type
     * @return $this
     */
    public function saveTypeStoreData($type)
    {
        $this->getConnection()->delete(
            $this->_infoTable,
            ['type_id = ?' => (int)$type->getId(), 'store_id = ?' => (int)$type->getStoreId()]
        );

        $this->getConnection()->insert(
            $this->_infoTable,
            [
                'type_id' => (int)$type->getId(),
                'store_id' => (int)$type->getStoreId(),
                'label' => $type->getLabel(),
                'is_listed' => (int)$type->getIsListed(),
                'sort_order' => (int)$type->getSortOrder()
            ]
        );

        return $this;
    }

    /**
     * Save store data
     *
     * @param \Magento\GiftRegistry\Model\Type $type
     * @param array $data
     * @param string $optionCode
     * @return $this
     */
    public function saveStoreData($type, $data, $optionCode = '')
    {
        $connection = $this->getConnection();
        if (isset($data['use_default'])) {
            $connection->delete(
                $this->_labelTable,
                [
                    'type_id = ?' => (int)$type->getId(),
                    'attribute_code = ?' => $data['code'],
                    'store_id = ?' => (int)$type->getStoreId(),
                    'option_code = ?' => $optionCode
                ]
            );
        } else {
            $values = [
                'type_id' => (int)$type->getId(),
                'attribute_code' => $data['code'],
                'store_id' => (int)$type->getStoreId(),
                'option_code' => $optionCode,
                'label' => $data['label'],
            ];
            $connection->insertOnDuplicate($this->_labelTable, $values, ['label']);
        }

        return $this;
    }

    /**
     * Get attribute store data
     *
     * @param \Magento\GiftRegistry\Model\Type $type
     * @return array
     */
    public function getAttributesStoreData($type)
    {
        $select = $this->getConnection()->select()->from(
            $this->_labelTable,
            ['attribute_code', 'option_code', 'label']
        )->where(
            'type_id = :type_id'
        )->where(
            'store_id = :store_id'
        );
        $bind = [':type_id' => (int)$type->getId(), ':store_id' => (int)$type->getStoreId()];
        return $this->getConnection()->fetchAll($select, $bind);
    }

    /**
     * Delete attribute store data
     *
     * @param int $typeId
     * @param string $attributeCode
     * @param string $optionCode
     * @return $this
     */
    public function deleteAttributeStoreData($typeId, $attributeCode, $optionCode = null)
    {
        $where = ['type_id = ?' => (int)$typeId, 'attribute_code = ?' => $attributeCode];

        if ($optionCode !== null) {
            $where['option_code = ?'] = $optionCode;
        }

        $this->getConnection()->delete($this->_labelTable, $where);
        return $this;
    }

    /**
     * Delete attribute values
     *
     * @param int $typeId
     * @param string $attributeCode
     * @param bool $personValue
     * @return $this
     */
    public function deleteAttributeValues($typeId, $attributeCode, $personValue = false)
    {
        $entityTable = $this->getTable('magento_giftregistry_entity');
        $select = $this->getConnection()->select();
        $select->from(['e' => $entityTable], ['entity_id'])->where('type_id = ?', (int)$typeId);

        if ($personValue) {
            $table = $this->getTable('magento_giftregistry_person');
        } else {
            $table = $this->getTable('magento_giftregistry_data');
        }

        $this->getConnection()->update(
            $table,
            [$attributeCode => new \Zend_Db_Expr('NULL')],
            ['entity_id IN (?)' => $select]
        );

        return $this;
    }
}
