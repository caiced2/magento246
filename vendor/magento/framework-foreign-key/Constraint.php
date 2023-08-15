<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey;

use Magento\Framework\ForeignKey\StrategyInterface;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Constraint implements ConstraintInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var string
     */
    private $referenceConnection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $referenceTableName;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $referenceFieldName;

    /**
     * @var string
     */
    private $deleteStrategy;

    /**
     * @var ConstraintInterface[]
     */
    private $subConstraints;

    /**
     * @var string[]
     */
    private $tableAffectedFields;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param string $name
     * @param string $connectionName
     * @param string $referenceConnection
     * @param string $tableName
     * @param string $referenceTableName
     * @param string $fieldName
     * @param string $referenceFieldName
     * @param string $deleteStrategy
     * @param array $subConstraints
     * @param array $tableAffectedFields
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        $name,
        $connectionName,
        $referenceConnection,
        $tableName,
        $referenceTableName,
        $fieldName,
        $referenceFieldName,
        $deleteStrategy,
        array $subConstraints,
        array $tableAffectedFields
    ) {
        $this->resource = $resource;
        $this->name = $name;
        $this->connectionName = $connectionName;
        $this->referenceConnection = $referenceConnection;
        $this->tableName = $tableName;
        $this->referenceTableName = $referenceTableName;
        $this->fieldName = $fieldName;
        $this->referenceFieldName = $referenceFieldName;
        $this->deleteStrategy = $deleteStrategy;
        $this->subConstraints = $subConstraints;
        $this->tableAffectedFields = $tableAffectedFields;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->resource->getConnectionByName($this->connectionName);
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getReferenceConnection()
    {
        return $this->resource->getConnectionByName($this->referenceConnection);
    }

    /**
     * @param array $values
     * @return string
     */
    public function getCondition(array $values)
    {
        $text = $this->fieldName . " IN(?)";
        return $this->getConnection()->quoteInto($text, $values);
    }

    /**
     * @return ConstraintInterface[]
     */
    public function getSubConstraints()
    {
        $allowed = [StrategyInterface::TYPE_CASCADE, 'DB ' . StrategyInterface::TYPE_CASCADE];
        return in_array($this->deleteStrategy, $allowed) ? $this->subConstraints : [];
    }

    /**
     * @return string
     * @codeCoverageIgnoreStart
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getReferenceTableName()
    {
        return $this->referenceTableName;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getReferenceField()
    {
        return $this->referenceFieldName;
    }

    /**
     * @return string
     */
    public function getStrategy()
    {
        return $this->deleteStrategy;
    }

    /**
     * @return string[]
     */
    public function getSubConstraintsAffectedFields()
    {
        return $this->tableAffectedFields;
    }

    //@codeCoverageIgnoreEnd
}
