<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Rule\Condition;

/**
 * @api
 * @since 100.0.2
 */
class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var \Magento\TargetRule\Model\Rule\Condition\Product\AttributesFactory
     */
    protected $_attributeFactory;

    /**
     * @var \Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder
     */
    private $conditionSqlBuilder;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\TargetRule\Model\Rule\Condition\Product\AttributesFactory $attributesFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\TargetRule\Model\Rule\Condition\Product\AttributesFactory $attributesFactory,
        array $data = [],
        \Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder $conditionSqlBuilder = null
    ) {
        $this->_attributeFactory = $attributesFactory;
        parent::__construct($context, $data);
        $this->setType(\Magento\TargetRule\Model\Rule\Condition\Combine::class);
        $this->conditionSqlBuilder = $conditionSqlBuilder ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder::class);
    }

    /**
     * Prepare list of contitions
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = [
            ['value' => $this->getType(), 'label' => __('Conditions Combination')],
            $this->_attributeFactory->create()->getNewChildSelectOptions(),
        ];

        $conditions = array_merge_recursive(parent::getNewChildSelectOptions(), $conditions);
        return $conditions;
    }

    /**
     * Collect validated attributes for Product Collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @return $this
     */
    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }

    /**
     * Retrieve SELECT WHERE condition for product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Zend_Db_Expr|false
     * @since 101.0.0
     */
    public function getConditionForCollection($collection)
    {
        $conditions = [];
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $operator = $this->getValue() ? '' : 'NOT';

        foreach ($this->getConditions() as $condition) {
            if ($condition instanceof Combine) {
                $subCondition = $condition->getConditionForCollection($collection);
            } else {
                $subCondition = $this->conditionSqlBuilder->generateWhereClause($condition);
            }
            if ($subCondition) {
                $conditions[] = sprintf('%s %s', $operator, $subCondition);
            }
        }

        if ($conditions) {
            return new \Zend_Db_Expr(sprintf('(%s)', join($aggregator, $conditions)));
        }

        return false;
    }
}
