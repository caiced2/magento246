<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Actions\Condition;

use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;
use Magento\TargetRule\Model\Actions\Condition\Product\Special\Price;

/**
 * Combine
 *
 * @api
 * @since 100.0.2
 */
class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var \Magento\TargetRule\Model\Actions\Condition\Product\AttributesFactory
     */
    protected $_attributeFactory;

    /**
     * @var \Magento\TargetRule\Model\Actions\Condition\Product\SpecialFactory
     */
    protected $_specialFactory;

    /**
     * @var SqlBuilder
     */
    private $conditionSqlBuilder;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\TargetRule\Model\Actions\Condition\Product\AttributesFactory $attributeFactory
     * @param \Magento\TargetRule\Model\Actions\Condition\Product\SpecialFactory $specialFactory
     * @param array $data
     * @param SqlBuilder $conditionSqlBuilder
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\TargetRule\Model\Actions\Condition\Product\AttributesFactory $attributeFactory,
        \Magento\TargetRule\Model\Actions\Condition\Product\SpecialFactory $specialFactory,
        array $data = [],
        SqlBuilder $conditionSqlBuilder = null
    ) {
        $this->_attributeFactory = $attributeFactory;
        $this->_specialFactory = $specialFactory;
        parent::__construct($context, $data);
        $this->setType(\Magento\TargetRule\Model\Actions\Condition\Combine::class);
        $this->conditionSqlBuilder = $conditionSqlBuilder
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(SqlBuilder::class);
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
            $this->_specialFactory->create()->getNewChildSelectOptions(),
        ];
        $conditions = array_merge_recursive(parent::getNewChildSelectOptions(), $conditions);
        return $conditions;
    }

    /**
     * Retrieve SELECT WHERE condition for product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\TargetRule\Model\Index $object
     * @param array &$bind
     * @return \Zend_Db_Expr|false
     */
    public function getConditionForCollection($collection, $object, &$bind)
    {
        $conditions = [];
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $operator = $this->getValue() ? '' : 'NOT';

        foreach ($this->getConditions() as $condition) {
            if ($condition instanceof Combine || $condition instanceof Price) {
                $subCondition = $condition->getConditionForCollection($collection, $object, $bind);
            } else {
                $subCondition = $this->conditionSqlBuilder->generateWhereClause(
                    $condition,
                    $bind,
                    $object->getStoreId(),
                    $object->select()
                );
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
