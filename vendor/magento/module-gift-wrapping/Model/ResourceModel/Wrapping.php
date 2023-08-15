<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\ResourceModel;

/**
 * Gift Wrapping Resource Model
 *
 */
class Wrapping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Wrapping websites table name
     *
     * @var string
     */
    protected $_websiteTable;

    /**
     * Wrapping stores data table name
     *
     * @var string
     */
    protected $_storeAttributesTable;

    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftwrapping', 'wrapping_id');
        $this->_websiteTable = $this->getTable('magento_giftwrapping_website');
        $this->_storeAttributesTable = $this->getTable('magento_giftwrapping_store_attributes');
    }

    /**
     * Add store data to wrapping data
     *
     * @param  \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->_storeAttributesTable,
            [
                'scope' => $connection->getCheckSql(
                    'store_id = 0',
                    $connection->quote('default'),
                    $connection->quote('store')
                ),
                'design'
            ]
        )->where(
            'wrapping_id = ?',
            $object->getId()
        )->where(
            'store_id IN (0,?)',
            $object->getStoreId()
        );

        $data = $connection->fetchAssoc($select);

        if (isset($data['store']) && is_array($data['store'])) {
            foreach ($data['store'] as $key => $value) {
                $object->setData($key, $value !== null ? $value : $data['default'][$key]);
                $object->setData($key . '_store', $value);
            }
        } elseif (isset($data['default'])) {
            foreach ($data['default'] as $key => $value) {
                $object->setData($key, $value);
            }
        }
        return parent::_afterLoad($object);
    }

    /**
     * Get website ids associated to the gift wrapping
     *
     * @param  int $wrappingId
     * @return array
     */
    public function getWebsiteIds($wrappingId)
    {
        $select = $this->getConnection()->select()->from(
            $this->_websiteTable,
            'website_id'
        )->where(
            'wrapping_id = ?',
            $wrappingId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Save wrapping per store view data
     *
     * @param  \Magento\GiftWrapping\Model\Wrapping $wrapping
     * @return void
     */
    public function saveWrappingStoreData($wrapping)
    {
        $initialDesign = $wrapping->getDesign();
        //this check to prevent saving default data from store view
        if ($wrapping->hasData('is_default') && is_array($wrapping->getData('is_default'))) {
            foreach ($wrapping->getData('is_default') as $key => $value) {
                if ($value) {
                    $wrapping->setData($key, null);
                }
            }
        }

        if ($initialDesign !== null) {
            $this->getConnection()->delete(
                $this->_storeAttributesTable,
                ['wrapping_id = ?' => $wrapping->getId(), 'store_id = ?' => $wrapping->getStoreId()]
            );

            if ($wrapping->getDesign()) {
                $this->getConnection()->insert(
                    $this->_storeAttributesTable,
                    [
                        'wrapping_id' => $wrapping->getId(),
                        'store_id' => $wrapping->getStoreId(),
                        'design' => $wrapping->getDesign()
                    ]
                );
            }
        }
    }

    /**
     * Save attached websites
     *
     * @param  \Magento\GiftWrapping\Model\Wrapping $wrapping
     * @return void
     */
    public function saveWrappingWebsiteData($wrapping)
    {
        $websiteIds = $wrapping->getWebsiteIds();
        $this->getConnection()->delete($this->_websiteTable, ['wrapping_id = ?' => $wrapping->getId()]);

        foreach ($websiteIds as $value) {
            $this->getConnection()->insert(
                $this->_websiteTable,
                ['wrapping_id' => $wrapping->getId(), 'website_id' => $value]
            );
        }
    }

    /**
     * Update gift wrapping status
     *
     * @param int $status new status can be 1 or 0
     * @param array $wrappingIds target wrapping IDs
     * @return void
     */
    public function updateStatus($status, array $wrappingIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['status' => (int)(bool)$status],
            ['wrapping_id IN(?)' => $wrappingIds]
        );
    }
}
