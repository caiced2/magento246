<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Product;

use Magento\Customer\Model\Customer;
use Zend_Db_Expr;

/**
 * Product attributes condition combine
 */
class Combine extends \Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine
{
    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Product\Combine::class);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $children = array_merge_recursive(
            parent::getNewChildSelectOptions(),
            [['value' => $this->getType(), 'label' => __('Conditions Combination')]]
        );
        if ($this->getDateConditions()) {
            $children = array_merge_recursive(
                $children,
                [
                    [
                        'value' => [
                            $this->_conditionFactory->create('Uptodate')->getNewChildSelectOptions(),
                            $this->_conditionFactory->create('Daterange')->getNewChildSelectOptions(),
                        ],
                        'label' => __('Date Ranges'),
                    ]
                ]
            );
        }
        $children = array_merge_recursive(
            $children,
            [$this->_conditionFactory->create('Product\Attributes')->getNewChildSelectOptions()]
        );
        return $children;
    }

    /**
     * Combine not present his own SQL condition
     *
     * @param Customer|Zend_Db_Expr $customer
     * @param int|Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        return false;
    }

    /**
     * Get combine subfilter type
     *
     * @return string
     */
    public function getSubfilterType()
    {
        return 'product';
    }

    /**
     * Apply product attribute subfilter to parent/base condition query
     *
     * @param string $fieldName base query field name
     * @param bool $requireValid strict validation flag
     * @param int|Zend_Db_Expr $website
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return string
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        $table = $this->getResource()->getTable('catalog_product_entity');

        $select = $this->getResource()->createSelect();
        $select->from(['main' => $table], ['entity_id']);

        if ($this->getAggregator() == 'all') {
            $whereFunction = 'where';
        } else {
            $whereFunction = 'orWhere';
        }

        $gotConditions = false;
        foreach ($this->getConditions() as $condition) {
            if ($condition->getSubfilterType() == 'product') {
                $subfilter = $condition->getSubfilterSql('main.entity_id', $this->getValue() == 1, $website);
                if ($subfilter) {
                    $select->{$whereFunction}($subfilter);
                    $gotConditions = true;
                }
            }
        }
        if (!$gotConditions) {
            $select->where('TRUE');
        }

        $inOperator = $requireValid ? 'IN' : 'NOT IN';
        $entityIds = implode(',', $this->getResource()->getConnection()->fetchCol($select));
        if (empty($entityIds)) {
            return $requireValid ? "FALSE" : "TRUE";
        }
        return sprintf("%s %s (%s)", $fieldName, $inOperator, $entityIds);
    }

    /**
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $result = false;
        $select = $this->getConditionsSql($customer, $websiteId);
        if (isset($select)) {
            $matchedParams = $this->matchParameters($select, $params);
            $result = $this->getResource()->getConnection()->fetchOne($select, $matchedParams);
        }
        return $result > 0;
    }

    /**
     * @param int $websiteId
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        $result = [];
        $select = $this->getConditionsSql(null, $websiteId, false);
        if (isset($select)) {
            $result = $this->getResource()->getConnection()->fetchCol($select);
        }
        return $result;
    }
}
