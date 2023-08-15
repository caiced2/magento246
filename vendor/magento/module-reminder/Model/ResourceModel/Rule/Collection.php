<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\ResourceModel\Rule;

/**
 * Reminder rules resource collection model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection
{
    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website' => [
            'associations_table' => 'magento_reminder_rule_website',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'website_id',
        ],
    ];

    /**
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Reminder\Model\Rule::class, \Magento\Reminder\Model\ResourceModel\Rule::class);
        $this->addFilterToMap('rule_id', 'main_table.rule_id');
    }

    /**
     * Limit rules collection by date columns
     *
     * @param string $date
     * @return $this
     */
    public function addDateFilter($date)
    {
        $this->getSelect()->where(
            'from_date IS NULL OR from_date <= ?',
            $date
        )->where(
            'to_date IS NULL OR to_date >= ?',
            $date
        );

        return $this;
    }

    /**
     * Limit rules collection by separate rule
     *
     * @param int $value
     * @return $this
     */
    public function addRuleFilter($value)
    {
        $this->getSelect()->where('main_table.rule_id = ?', $value);
        return $this;
    }
}
