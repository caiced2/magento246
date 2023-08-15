<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\ResourceModel\Type;

/**
 * Gift refistry type resource collection
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * If the table was joined flag
     *
     * @var bool
     */
    protected $_isTableJoined = false;

    /**
     * Collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftRegistry\Model\Type::class, \Magento\GiftRegistry\Model\ResourceModel\Type::class);
    }

    /**
     * Add store data to collection
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreData($storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID)
    {
        $infoTable = $this->getTable('magento_giftregistry_type_info');
        $connection = $this->getConnection();

        $select = $connection->select();
        $select->from(
            ['m' => $this->getMainTable()]
        )->joinInner(
            ['d' => $infoTable],
            $connection->quoteInto(
                'm.type_id = d.type_id AND d.store_id = ?',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ),
            []
        )->joinLeft(
            ['s' => $infoTable],
            $connection->quoteInto('s.type_id = m.type_id AND s.store_id = ?', (int)$storeId),
            [
                'label' => $connection->getCheckSql('s.label IS NULL', 'd.label', 's.label'),
                'is_listed' => $connection->getCheckSql('s.is_listed IS NULL', 'd.is_listed', 's.is_listed'),
                'sort_order' => $connection->getCheckSql('s.sort_order IS NULL', 'd.sort_order', 's.sort_order')
            ]
        );

        $this->getSelect()->reset()->from(['main_table' => $select]);

        $this->_isTableJoined = true;

        return $this;
    }

    /**
     * Filter collection by listed param
     *
     * @return $this
     */
    public function applyListedFilter()
    {
        if ($this->_isTableJoined) {
            $this->getSelect()->where('is_listed = ?', 1);
        }
        return $this;
    }

    /**
     * Apply sorting by sort_order param
     *
     * @return $this
     */
    public function applySortOrder()
    {
        if ($this->_isTableJoined) {
            $this->getSelect()->order('sort_order');
        }
        return $this;
    }

    /**
     * Convert collection to array for select options
     *
     * @param bool $withEmpty
     * @return array
     */
    public function toOptionArray($withEmpty = false)
    {
        $result = $this->_toOptionArray('type_id', 'label');
        if ($withEmpty) {
            $result = array_merge([['value' => '', 'label' => __('-- All --')]], $result);
        }
        return $result;
    }
}
