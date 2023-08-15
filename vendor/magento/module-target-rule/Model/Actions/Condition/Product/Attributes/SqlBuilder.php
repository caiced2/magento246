<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Actions\Condition\Product\Attributes;

use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;

/**
 * Target rule SQL builder is used to construct SQL conditions for 'products to display'.
 */
class SqlBuilder extends \Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder
{
    /**
     * @inheritdoc
     */
    protected function shouldUseBind($condition)
    {
        return $condition->getValueType() != Attributes::VALUE_TYPE_CONSTANT;
    }

    /**
     * @inheritdoc
     */
    protected function normalizeConditionValue($condition)
    {
        $value = $condition->getValue();
        if ($condition->getValueType() == Attributes::VALUE_TYPE_CONSTANT) {
            $operator = $condition->getOperator();
            // split value by commas into array for operators with multiple operands
            if (($operator == '()' || $operator == '!()') && is_string($value) && trim($value) != '') {
                $value = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
            }
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    protected function addCategoryIdsCondition(
        $select,
        $condition,
        &$bind
    ) {
        $valueType = $condition->getValueType();
        if ($valueType == Attributes::VALUE_TYPE_SAME_AS) {
            $operator = $this->getCategoryIdsConditionOperator($condition->getOperator());
            if ($operator === '!()') {
                $where = $this->indexResource->getOperatorBindCondition(
                    'category_id',
                    'category_ids',
                    '()',
                    $bind,
                    ['bindArrayOfIds']
                );
                $select->where($where);
                return new \Zend_Db_Expr(sprintf('(%s) = 0', $select->assemble()));
            }
            $where = $this->indexResource->getOperatorBindCondition(
                'category_id',
                'category_ids',
                $operator,
                $bind,
                ['bindArrayOfIds']
            );
            $select->where($where);
        } elseif ($valueType == Attributes::VALUE_TYPE_CHILD_OF) {
            $concatenated = $this->indexResource->getConnection()->getConcatSql(['tp.path', "'/%'"]);
            $subSelect = $this->indexResource->select()->from(
                ['tc' => $this->indexResource->getTable('catalog_category_entity')],
                'entity_id'
            )->join(
                ['tp' => $this->indexResource->getTable('catalog_category_entity')],
                "tc.path " . ($condition->getOperator() == '!()' ? 'NOT ' : '') . "LIKE {$concatenated}",
                []
            )->where(
                $this->indexResource->getOperatorBindCondition(
                    'tp.entity_id',
                    'category_ids',
                    '()',
                    $bind,
                    ['bindArrayOfIds']
                )
            );
            $select->where('category_id IN(?)', $subSelect);
        } else {
            return parent::addCategoryIdsCondition($select, $condition, $bind);
        }

        return new \Zend_Db_Expr(sprintf('(%s) > 0', $select->assemble()));
    }
}
