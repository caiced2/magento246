<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Collection of banner <-> catalog rule associations
 */

namespace Magento\Banner\Model\ResourceModel\Catalogrule;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magento_banner_catalogrule_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'collection';

    /**
     * Define collection item type and corresponding table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Framework\DataObject::class, \Magento\CatalogRule\Model\ResourceModel\Rule::class);
        $this->setMainTable('magento_banner_catalogrule');
    }

    /**
     * Filter out disabled banners
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->join(
                ['banner' => $this->getTable('magento_banner')],
                'banner.banner_id = main_table.banner_id AND banner.is_enabled = 1',
                []
            )
            ->join(
                ['catalogrule' => $this->getTable('catalogrule')],
                'catalogrule.rule_id = main_table.rule_id',
                []
            )
            ->group(
                'main_table.banner_id'
            );
        return $this;
    }

    /**
     * Add website id and customer group id filter to the collection
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @return \Magento\Banner\Model\ResourceModel\Catalogrule\Collection
     */
    public function addWebsiteCustomerGroupFilter($websiteId, $customerGroupId)
    {
        $this->getSelect()->join(
            ['rule_group_website' => $this->getTable('catalogrule_group_website')],
            'rule_group_website.rule_id = main_table.rule_id',
            []
        )->where(
            'rule_group_website.customer_group_id = ?',
            $customerGroupId
        )->where(
            'rule_group_website.website_id = ?',
            $websiteId
        );
        return $this;
    }
}
