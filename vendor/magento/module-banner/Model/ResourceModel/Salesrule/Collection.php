<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Model\ResourceModel\Salesrule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\SalesRule\Model\ResourceModel\Rule;
use Magento\Framework\DataObject;

/**
 * Collection of banner <-> sales rule associations
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magento_banner_salesrule_collection';

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
        $this->_init(DataObject::class, Rule::class);
        $this->setMainTable('magento_banner_salesrule');
    }

    /**
     * Filter out disabled banners
     *
     * @return $this
     */
    protected function _initSelect(): self
    {
        parent::_initSelect();
        $this->getSelect()->join(
            ['banner' => $this->getTable('magento_banner')],
            'banner.banner_id = main_table.banner_id AND banner.is_enabled = 1',
            []
        )->group(
            'main_table.banner_id'
        );
        return $this;
    }

    /**
     * Add sales rule ids filter to the collection
     *
     * @param array $ruleIds
     * @return $this
     */
    public function addRuleIdsFilter(array $ruleIds): self
    {
        if (!$ruleIds) {
            // force to match no rules
            $ruleIds = [0];
        }
        $this->addFieldToFilter('main_table.rule_id', ['in' => $ruleIds]);
        return $this;
    }

    /**
     * Filter collection to only active or inactive rules
     *
     * @param int $isActive
     * @return $this
     */
    public function addIsActiveSalesRuleFilter(int $isActive = 1): self
    {
        $this->joinRelatedSalesRule();
        $this->addFieldToFilter('sales_rules.is_active', $isActive);
        return $this;
    }

    /**
     * Join Sales Rule table
     *
     * @return $this
     */
    private function joinRelatedSalesRule(): self
    {
        $this->getSelect()->join(
            ['sales_rules' => $this->getTable('salesrule')],
            'main_table.rule_id = sales_rules.rule_id',
            ['rule_id', 'is_active']
        );

        return $this;
    }
}
