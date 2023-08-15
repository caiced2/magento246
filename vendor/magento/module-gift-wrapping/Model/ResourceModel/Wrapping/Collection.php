<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\ResourceModel\Wrapping;

/**
 * Gift Wrapping Collection
 *
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Intialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\GiftWrapping\Model\Wrapping::class,
            \Magento\GiftWrapping\Model\ResourceModel\Wrapping::class
        );
        $this->_map['fields']['wrapping_id'] = 'main_table.wrapping_id';
    }

    /**
     * Redeclare after load method to add website IDs to items
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getFlag('add_websites_to_result') && $this->_items) {
            $select = $this->getConnection()->select()->from(
                $this->getTable('magento_giftwrapping_website'),
                ['wrapping_id', 'website_id']
            )->where(
                'wrapping_id IN (?)',
                array_keys($this->_items)
            );
            $websites = $this->getConnection()->fetchAll($select);
            foreach ($this->_items as $item) {
                $websiteIds = [];
                foreach ($websites as $website) {
                    if ($item->getId() == $website['wrapping_id']) {
                        $websiteIds[] = $website['website_id'];
                    }
                }
                if (count($websiteIds)) {
                    $item->setWebsiteIds($websiteIds);
                }
            }
        }
        return $this;
    }

    /**
     * Init flag for adding wrapping website ids to collection result
     *
     * @param  bool|null $flag
     * @return $this
     */
    public function addWebsitesToResult($flag = null)
    {
        $flag = $flag === null ? true : $flag;
        $this->setFlag('add_websites_to_result', $flag);
        return $this;
    }

    /**
     * Limit gift wrapping collection by specific website
     *
     * @param  int|array|\Magento\Store\Model\Website $websiteId
     * @return $this
     */
    public function applyWebsiteFilter($websiteId)
    {
        if (!$this->getFlag('is_website_table_joined')) {
            $this->setFlag('is_website_table_joined', true);
            $this->getSelect()->joinInner(
                ['website' => $this->getTable('magento_giftwrapping_website')],
                'main_table.wrapping_id = website.wrapping_id',
                []
            );
        }

        if ($websiteId instanceof \Magento\Store\Model\Website) {
            $websiteId = $websiteId->getId();
        }
        $this->getSelect()->where('website.website_id IN (?)', $websiteId);

        return $this;
    }

    /**
     * Limit gift wrapping collection by status
     *
     * @return $this
     * @codeCoverageIgnore
     */
    public function applyStatusFilter()
    {
        $this->getSelect()->where('main_table.status = 1');
        return $this;
    }

    /**
     * Add specified field to collection filter
     * Redeclared in order to be able to limit collection by specific website
     *
     * @param  string $field
     * @param  mixed $condition
     * @return $this
     * @see self::applyWebsiteFilter()
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'website_ids') {
            return $this->applyWebsiteFilter($condition);
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Convert collection to array for select options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array_merge(
            [['value' => '', 'label' => __('Please select')]],
            $this->_toOptionArray('wrapping_id', 'design')
        );
    }

    /**
     * Add store attributes to collection
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreAttributesToResult($storeId = 0)
    {
        $connection = $this->getConnection();
        if ((int)$storeId !== 0) {
            $this->getSelect()
                ->joinLeft(
                    ['d' => $this->getTable('magento_giftwrapping_store_attributes')],
                    'd.wrapping_id = main_table.wrapping_id AND d.store_id = 0',
                    []
                )
                ->joinLeft(
                    ['s' => $this->getTable('magento_giftwrapping_store_attributes')],
                    's.wrapping_id = main_table.wrapping_id AND s.store_id = ' . $storeId,
                    ['design' => $connection->getIfNullSql('s.design', 'd.design')]
                )
                ->group('main_table.wrapping_id');
        } else {
            $this->getSelect()
                ->joinLeft(
                    ['s' => $this->getTable('magento_giftwrapping_store_attributes')],
                    's.wrapping_id = main_table.wrapping_id AND s.store_id = 0',
                    ['design' => 's.design']
                )
                ->group('main_table.wrapping_id');
        }

        return $this;
    }
}
