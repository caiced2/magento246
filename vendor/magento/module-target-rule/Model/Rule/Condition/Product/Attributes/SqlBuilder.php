<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Rule\Condition\Product\Attributes;

use Magento\Rule\Model\Condition\Product\AbstractProduct as ProductCondition;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Select;

/**
 * Target rule SQL builder is used to construct SQL conditions for 'matching products'.
 */
class SqlBuilder
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Index
     */
    protected $indexResource;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\TargetRule\Model\ResourceModel\Index $indexResource
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\TargetRule\Model\ResourceModel\Index $indexResource
    ) {
        $this->metadataPool = $metadataPool;
        $this->indexResource = $indexResource;
    }

    /**
     * Generate WHERE clause based on provided condition.
     *
     * @param ProductCondition $condition
     * @param array $bind
     * @param int|null $storeId
     * @param Select|null $select
     * @return bool|\Zend_Db_Expr
     */
    public function generateWhereClause(
        ProductCondition $condition,
        &$bind = [],
        $storeId = null,
        Select $select = null
    ) {
        $select = $select ?: $this->indexResource->getConnection()->select();

        if ($condition->getAttribute() == 'category_ids') {
            $select->from(
                $this->indexResource->getTable('catalog_category_product'),
                'COUNT(*)'
            )->where(
                'product_id=e.entity_id'
            );
            return $this->addCategoryIdsCondition($select, $condition, $bind);
        }
        $where = $this->addAttributeCondition($select, $condition, $bind, $storeId);
        return false !== $where ? new \Zend_Db_Expr($where) : false;
    }

    /**
     * Modify conditions for collection with category_ids attribute
     *
     * - For conditions like (e.g "Product Category contains Constant Value 6"),
     * the generated SQL will look like the following:
     * (SELECT COUNT(*) FROM `catalog_category_product` WHERE ... AND `category_id` IN ('6')) > 0
     * - For conditions like (e.g "Product Category does not contain  Constant Value 6"),
     * the generated SQL will look like the following:
     * (SELECT COUNT(*) FROM `catalog_category_product` WHERE ... AND `category_id` IN ('6')) = 0
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @return \Zend_Db_Expr
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function addCategoryIdsCondition(
        $select,
        $condition,
        &$bind
    ) {
        $operator = $this->getCategoryIdsConditionOperator($condition->getOperator());
        $value = $this->indexResource->bindArrayOfIds($condition->getValue());
        if ($operator === '!()') {
            $where = $this->indexResource->getOperatorCondition('category_id', '()', $value);
            $select->where($where);
            return new \Zend_Db_Expr(sprintf('(%s) = 0', $select->assemble()));
        }
        $where = $this->indexResource->getOperatorCondition('category_id', $operator, $value);
        $select->where($where);
        return new \Zend_Db_Expr(sprintf('(%s) > 0', $select->assemble()));
    }

    /**
     * Get operator for category_ids attribute condition
     *
     * @param string $conditionOperator
     * @return string
     */
    public function getCategoryIdsConditionOperator(string $conditionOperator): string
    {
        if (in_array($conditionOperator, ['!{}', '!='])) {
            $operator = '!()';
        } elseif (in_array($conditionOperator, ['{}', '=='])) {
            $operator = '()';
        } else {
            $operator = $conditionOperator;
        }
        return $operator;
    }

    /**
     * Add condition based on product attribute.
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @param int $storeId
     * @return array|bool|string
     */
    protected function addAttributeCondition(
        $select,
        $condition,
        &$bind,
        $storeId
    ) {
        $attribute = $condition->getAttributeObject();

        if (!$attribute) {
            return false;
        }
        $attributeCode = $condition->getAttribute();
        $operator = $condition->getOperator();
        if ($attribute->isStatic()) {
            $field = "e.{$attributeCode}";
            if ($this->shouldUseBind($condition)) {
                $where = $this->indexResource->getOperatorBindCondition($field, $attributeCode, $operator, $bind);
            } else {
                $value = $this->normalizeConditionValue($condition);
                $where = $this->indexResource->getOperatorCondition($field, $operator, $value);
            }
            $where = sprintf('(%s)', $where);
        } elseif ($attribute->isScopeGlobal()) {
            $where = $this->addGlobalAttributeConditions(
                $select,
                $condition,
                $bind
            );
        } else {
            $where = $this->addScopedAttributeConditions(
                $select,
                $condition,
                $bind,
                $storeId
            );
        }
        return $where;
    }

    /**
     * Add condition based on attribute with store or website scope.
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @param int $storeId
     * @return string
     */
    private function addScopedAttributeConditions(
        $select,
        $condition,
        array &$bind,
        $storeId
    ) {
        $valueExpr = $this->indexResource->getConnection()->getCheckSql(
            'attr_s.value_id > 0',
            'attr_s.value',
            'attr_d.value'
        );
        $attribute = $condition->getAttributeObject();
        $table = $attribute->getBackendTable();
        $entityFieldName = $this->metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();

        $select->from(
            ['attr_d' => $table],
            'COUNT(*)'
        )->joinLeft(
            ['attr_s' => $table],
            $this->indexResource->getConnection()->quoteInto(
                sprintf(
                    'attr_s.%s = attr_d.%s AND attr_s.attribute_id = attr_d.attribute_id AND attr_s.store_id=?',
                    $entityFieldName,
                    $entityFieldName
                ),
                $storeId
            ),
            []
        )->where(
            sprintf('attr_d.%s = e.%s', $entityFieldName, $entityFieldName)
        )->where(
            'attr_d.attribute_id=?',
            $attribute->getId()
        )->where(
            'attr_d.store_id=?',
            Store::DEFAULT_STORE_ID
        );
        if ($this->shouldUseBind($condition)) {
            $select->where(
                $this->getOperatorBindCondition(
                    $condition,
                    $valueExpr,
                    $bind
                )
            );
        } else {
            $select->where(
                $this->getOperatorCondition(
                    $condition,
                    $valueExpr
                )
            );
        }

        $where = sprintf('(%s) > 0', $select);
        return $where;
    }

    /**
     * Add condition based on attribute with global scope.
     *
     * The 'catalog_product_relation' table added to allow select parent product entities by child products.
     *
     * The produced part of SELECT query looks like this:
     *
     * EXISTS (
     *  SELECT 1
     *  FROM `catalog_product_entity_int` AS `table`
     *  LEFT JOIN `catalog_product_entity` AS `cpe`
     *      ON cpe.row_id = table.row_id
     *  INNER JOIN `catalog_product_relation` AS `relation`
     *      ON cpe.entity_id = relation.child_id
     *  WHERE (relation.parent_id = e.row_id)
     *      AND (table.attribute_id='93')
     *      AND (table.store_id=0)
     *      AND (`table`.`value`='15')
     *  UNION
     *  SELECT 1
     *  FROM `catalog_product_entity_int` AS `table`
     *  WHERE (table.row_id = e.row_id)
     *      AND (table.attribute_id='487')
     *      AND (table.store_id=0)
     *      AND (`table`.`value`='15')
     * )
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @return string
     */
    private function addGlobalAttributeConditions(
        $select,
        $condition,
        array &$bind
    ) {
        $linkField = $this->metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();

        $attribute = $condition->getAttributeObject();
        $select->from(
            ['table' => $attribute->getBackendTable()],
            new \Zend_Db_Expr('1')
        )->joinLeft(
            ['cpe' => $this->indexResource->getTable('catalog_product_entity')],
            'cpe.' . $linkField . ' = table.' . $linkField,
            []
        )->joinInner(
            ['relation' => $this->indexResource->getTable('catalog_product_relation')],
            'cpe.entity_id = relation.child_id',
            []
        )
        ->where('relation.parent_id = e.' . $linkField)
        ->where('table.attribute_id=?', $attribute->getId())
        ->where('table.store_id=?', Store::DEFAULT_STORE_ID);

        if ($this->shouldUseBind($condition)) {
            $select->where(
                $this->getOperatorBindCondition(
                    $condition,
                    'table.value',
                    $bind
                )
            );
        } else {
            $select->where(
                $this->getOperatorCondition(
                    $condition,
                    'table.value'
                )
            );
        }

        $connection = $this->indexResource->getConnection();
        $selectChildren = $connection->select()
            ->from(
                ['table' => $attribute->getBackendTable()],
                new \Zend_Db_Expr('1')
            )
            ->where(sprintf('table.%s = e.%s', $linkField, $linkField))
            ->where('table.attribute_id=?', $attribute->getId())
            ->where('table.store_id=?', Store::DEFAULT_STORE_ID);

        if ($this->shouldUseBind($condition)) {
            $selectChildren->where(
                $this->getOperatorBindCondition(
                    $condition,
                    'table.value',
                    $bind
                )
            );
        } else {
            $selectChildren->where(
                $this->getOperatorCondition(
                    $condition,
                    'table.value'
                )
            );
        }

        $resultSelect = $this->indexResource->getConnection()->select()->union(
            [$select, $selectChildren],
            \Magento\Framework\DB\Select::SQL_UNION
        );

        return 'EXISTS (' . $resultSelect . ')';
    }

    /**
     * Check if binding should be used for specified condition.
     *
     * @param ProductCondition $condition
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldUseBind($condition)
    {
        return false;
    }

    /**
     * Normalize condition value to make it compatible with SQL operator associated with the condition.
     *
     * @param ProductCondition $condition
     * @return mixed
     */
    protected function normalizeConditionValue($condition)
    {
        return $condition->getValue();
    }

    /**
     * Generate SQL condition for attribute with bind value
     *
     * @param ProductCondition $condition
     * @param \Zend_Db_Expr|string $field
     * @param array $bind
     * @return string
     */
    private function getOperatorBindCondition(
        ProductCondition $condition,
        $field,
        array &$bind
    ): string {
        $attribute = $condition->getAttributeObject();
        switch ($attribute->getFrontendInput()) {
            case 'multiselect':
                $where = $this->getMultiselectAttributeBindCondition($condition, $field, $bind);
                break;
            default:
                $where = $this->indexResource->getOperatorBindCondition(
                    $field,
                    $condition->getAttribute(),
                    $condition->getOperator(),
                    $bind
                );
        }

        return $where;
    }

    /**
     * Generate SQL condition for attribute with constant value
     *
     * @param ProductCondition $condition
     * @param \Zend_Db_Expr|string $field
     * @return string
     */
    private function getOperatorCondition(
        ProductCondition $condition,
        $field
    ): string {
        $attribute = $condition->getAttributeObject();
        switch ($attribute->getFrontendInput()) {
            case 'multiselect':
                $where = $this->getMultiselectAttributeCondition($condition, $field);
                break;
            default:
                $where = $this->indexResource->getOperatorCondition(
                    $field,
                    $condition->getOperator(),
                    $condition->getValueParsed()
                );
        }

        return $where;
    }

    /**
     * Generate SQL condition for multiselect attribute with constant value
     *
     * @param ProductCondition $condition
     * @param \Zend_Db_Expr|string $field
     * @return string
     */
    public function getMultiselectAttributeCondition(
        ProductCondition $condition,
        $field
    ): string {
        // [contains, does not contain, is one of, is not one of]
        $operators = ['{}', '!{}', '()', '!()'];
        $operator = $condition->getOperator();
        if (in_array($operator, $operators)) {
            $orExp = [];
            foreach ($condition->getValueParsed() as $value) {
                $orExp[] =  ['finset' => $value];
            }
            $where = $this->indexResource->getConnection()->prepareSqlCondition($field, $orExp);
            if (strpos($operator, '!') === 0) {
                $where = sprintf('NOT %s', $where);
            }
        } else {
            $where = $this->indexResource->getOperatorCondition(
                $field,
                $condition->getOperator(),
                $condition->getValueParsed()
            );
        }
        return $where;
    }

    /**
     * Generate SQL condition for multiselect attribute with bind value
     *
     * @param ProductCondition $condition
     * @param \Zend_Db_Expr|string $field
     * @param array $bind
     * @return string
     */
    public function getMultiselectAttributeBindCondition(
        ProductCondition $condition,
        $field,
        array &$bind
    ): string {
        // contains, does not contain, is one of, is not one of
        $operators = ['{}', '!{}', '()', '!()'];
        $operator = $condition->getOperator();
        if (in_array($operator, $operators)) {
            $bindCount = count($bind);
            $where = $this->indexResource->getOperatorBindCondition(
                $field,
                $condition->getAttribute(),
                $condition->getOperator(),
                $bind
            );
            // check if new bind has been generated
            if ($bindCount < count($bind)) {
                $cloneBind = $bind;
                $newBind = end($cloneBind);
                $connection = $this->indexResource->getConnection();
                $regex = $connection->getConcatSql(
                    [
                        //e.g ,(REPLACE(:bind_name, ',', '|')),
                        "',('",
                        sprintf('REPLACE(%s,\',\',\'|\')', $newBind['bind']),
                        "'),'"
                    ]
                );
                $normalizedField = $connection->getConcatSql(["','", $field, "','"]);
                $where = $connection->prepareSqlCondition($normalizedField, ['regexp' => $regex]);
                if (strpos($operator, '!') === 0) {
                    $where = sprintf('NOT (%s)', $where);
                }
            }
        } else {
            $where = $this->indexResource->getOperatorBindCondition(
                $field,
                $condition->getAttribute(),
                $condition->getOperator(),
                $bind
            );
        }
        return $where;
    }
}
