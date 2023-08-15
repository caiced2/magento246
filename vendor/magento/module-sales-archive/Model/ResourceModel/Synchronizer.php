<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Model\ResourceModel;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;

/**
 * Module synchronizer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Synchronizer
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * Map of tables aliases to archive tables
     *
     * @var array
     */
    protected $_tablesMap = [
        'sales_order_grid' => 'magento_sales_order_grid_archive',
        'sales_invoice_grid' => 'magento_sales_invoice_grid_archive',
        'sales_creditmemo_grid' => 'magento_sales_creditmemo_grid_archive',
        'sales_shipment_grid' => 'magento_sales_shipment_grid_archive',
    ];

    /**
     * Map of flat tables to archive tables
     *
     * @var array
     */
    protected $_tableContraintMap = [
        'sales_order_grid' => ['SALES_ORDER_GRID', 'SALES_ORDER_GRID_ARCHIVE'],
        'sales_invoice_grid' => ['SALES_INVOICE_GRID', 'SALES_INVOICE_GRID_ARCHIVE'],
        'sales_creditmemo_grid' => ['SALES_CREDITMEMO_GRID', 'SALES_CREDITMEMO_GRID_ARCHIVE'],
        'sales_shipment_grid' => ['SALES_SHIPMENT_GRID', 'SALES_SHIPMENT_GRID_ARCHIVE'],
    ];

    /**
     * Default constructor
     *
     * @param ResourceConnection $resource
     * @param string $connectionName
     */
    public function __construct(
        ResourceConnection $resource,
        $connectionName = ResourceConnection::DEFAULT_CONNECTION
    ) {
        $this->resource = $resource;
        $this->connectionName = $connectionName;
        $this->connection = $this->resource->getConnection($this->connectionName);
    }

    /**
     * Synchronize archive structure
     *
     * @return $this
     */
    public function syncArchiveStructure()
    {
        foreach ($this->_tablesMap as $sourceTable => $targetTable) {
            $this->_syncTable(
                $this->resource->getTableName($sourceTable, $this->connectionName),
                $this->resource->getTableName($targetTable, $this->connectionName)
            );
        }
        return $this;
    }

    /**
     * Fast table describe retrieve
     *
     * @param string $table
     * @return array
     */
    protected function _fastDescribe($table)
    {
        $description = $this->connection->describeTable($table);
        $result = [];
        foreach ($description as $column) {
            $result[$column['COLUMN_NAME']] = $column['DATA_TYPE'];
        }
        return $result;
    }

    /**
     * Synchronize tables structure
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _syncTable($sourceTable, $targetTable)
    {
        if (!$this->connection->isTableExists($targetTable)) {
            $newTable = $this->connection->createTableByDdl($sourceTable, $targetTable);
            $this->connection->createTable($newTable);
        } else {
            $sourceFields = $this->connection->describeTable($sourceTable);
            $targetFields = $this->connection->describeTable($targetTable);
            foreach ($sourceFields as $field => $definition) {
                if (isset($targetFields[$field])) {
                    if ($this->_checkColumnDifference($targetFields[$field], $definition)) {
                        $this->connection->modifyColumnByDdl($targetTable, $field, $definition);
                    }
                } else {
                    $columnInfo = $this->connection->getColumnCreateByDescribe($definition);
                    $this->connection->addColumn($targetTable, $field, $columnInfo);
                    $targetFields[$field] = $definition;
                }
            }

            $previous = false;
            // Synchronize column positions
            $sourceFields = $this->_fastDescribe($sourceTable);
            $targetFields = $this->_fastDescribe($targetTable);
            foreach ($sourceFields as $field => $definition) {
                if ($previous === false) {
                    reset($targetFields);
                    if (key($targetFields) !== $field) {
                        $this->changeColumnPosition($targetTable, $field, false, true);
                    }
                } else {
                    reset($targetFields);
                    $currentKey = key($targetFields);
                    // Search for column position in target table
                    while ($currentKey !== $field) {
                        if (next($targetFields) === false) {
                            $currentKey = false;
                            break;
                        }
                        $currentKey = key($targetFields);
                    }
                    if ($currentKey) {
                        $moved = prev($targetFields) !== false;
                        // If column positions is different
                        if ($moved && $previous !== key($targetFields) || !$moved) {
                            $this->changeColumnPosition($targetTable, $field, $previous);
                        }
                    }
                }
                $previous = $field;
            }
            $this->_syncTableIndex($sourceTable, $targetTable);

            if (isset($this->_tableContraintMap[$sourceTable])) {
                $this->_syncTableConstraint(
                    $sourceTable,
                    $targetTable,
                    $this->_tableContraintMap[$sourceTable][0],
                    $this->_tableContraintMap[$sourceTable][1]
                );
            }
        }

        return $this;
    }

    /**
     * Change columns position
     *
     * @param string $table
     * @param string $column
     * @param bool $after
     * @param bool $first
     * @return $this
     */
    public function changeColumnPosition($table, $column, $after = false, $first = false)
    {
        $this->_changeColumnPosition($table, $column, $after, $first);
        return $this;
    }

    /**
     * Syncronize table indexes
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _syncTableIndex($sourceTable, $targetTable)
    {
        $sourceIndex = $this->connection->getIndexList($sourceTable);
        $targetIndex = $this->connection->getIndexList($targetTable);
        foreach ($sourceIndex as $indexKey => $indexData) {
            $indexExists = false;
            foreach ($targetIndex as $targetIndexKey => $targetIndexData) {
                if (!$this->_checkIndexDifference($indexData, $targetIndexData)) {
                    $indexExists = true;
                    break;
                }
            }
            if (!$indexExists) {
                $newIndexName = $this->connection->getIndexName(
                    $targetTable,
                    $indexData['COLUMNS_LIST'],
                    $indexData['INDEX_TYPE']
                );
                $this->connection->addIndex(
                    $targetTable,
                    $newIndexName,
                    $indexData['COLUMNS_LIST'],
                    $indexData['INDEX_TYPE']
                );
            }
        }

        return $this;
    }

    /**
     * Check column difference for synchronization
     *
     * @param array $sourceColumn
     * @param array $targetColumn
     * @return bool
     */
    protected function _checkColumnDifference($sourceColumn, $targetColumn)
    {
        unset($sourceColumn['TABLE_NAME']);
        unset($targetColumn['TABLE_NAME']);

        return $sourceColumn !== $targetColumn;
    }

    /**
     * Check indicies difference for synchronization
     *
     * @param array $sourceIndex
     * @param array $targetIndex
     * @return bool
     */
    protected function _checkIndexDifference($sourceIndex, $targetIndex)
    {
        return strtoupper(
            $sourceIndex['INDEX_TYPE']
        ) != strtoupper(
            $targetIndex['INDEX_TYPE']
        ) || count(
            array_diff($sourceIndex['COLUMNS_LIST'], $targetIndex['COLUMNS_LIST'])
        ) > 0;
    }

    /**
     * Check indexes difference for synchronization
     *
     * @param array $sourceConstraint
     * @param array $targetConstraint
     * @return bool
     */
    protected function _checkConstraintDifference($sourceConstraint, $targetConstraint)
    {
        return !$this->isItemEqual($sourceConstraint, $targetConstraint, 'COLUMN_NAME') ||
            !$this->isItemEqual($sourceConstraint, $targetConstraint, 'REF_TABLE_NAME') ||
            !$this->isItemEqual($sourceConstraint, $targetConstraint, 'REF_COLUMN_NAME') ||
            !$this->isItemEqual($sourceConstraint, $targetConstraint, 'ON_DELETE') ||
            !$this->isItemEqual($sourceConstraint, $targetConstraint, 'ON_UPDATE');
    }

    /**
     * Check if items are equal
     *
     * @param array $source
     * @param array $target
     * @param string $key
     * @return bool
     */
    protected function isItemEqual($source, $target, $key)
    {
        $sourceValue = isset($source[$key]) ? $source[$key] : null;
        $targetValue = isset($target[$key]) ? $target[$key] : null;
        return $sourceValue == $targetValue;
    }

    /**
     * Synchronize tables foreign keys
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @param string $sourceKey
     * @param string $targetKey
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _syncTableConstraint($sourceTable, $targetTable, $sourceKey, $targetKey)
    {
        $sourceConstraints = $this->connection->getForeignKeys($sourceTable);
        $targetConstraints = $this->connection->getForeignKeys($targetTable);

        $targetConstraintUsedInSource = [];
        foreach ($sourceConstraints as $constraintInfo) {
            $targetConstraint = $this->connection->getForeignKeyName(
                $targetTable,
                $constraintInfo['COLUMN_NAME'],
                $constraintInfo['REF_TABLE_NAME'],
                $constraintInfo['REF_COLUMN_NAME']
            );
            if (!isset(
                $targetConstraints[$targetConstraint]
            ) || $this->_checkConstraintDifference(
                $constraintInfo,
                $targetConstraints[$targetConstraint]
            )
            ) {
                $this->connection->addForeignKey(
                    $targetConstraint,
                    $targetTable,
                    $constraintInfo['COLUMN_NAME'],
                    $constraintInfo['REF_TABLE_NAME'],
                    $constraintInfo['REF_COLUMN_NAME'],
                    $constraintInfo['ON_DELETE']
                );
            }

            $targetConstraintUsedInSource[] = $targetConstraint;
        }

        $constraintToDelete = array_diff(array_keys($targetConstraints), $targetConstraintUsedInSource);

        foreach ($constraintToDelete as $constraint) {
            // Clear old not used constraints
            $this->connection->dropForeignKey($targetTable, $constraint);
        }

        return $this;
    }

    /**
     * Change columns position
     *
     * @param string $table
     * @param string $column
     * @param bool $after
     * @param bool $first
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _changeColumnPosition($table, $column, $after = false, $first = false)
    {
        if ($after && $first) {
            if (is_string($after)) {
                $first = false;
            } else {
                $after = false;
            }
        } elseif (!$after && !$first) {
            // If no new position specified
            return $this;
        }

        if (!$this->connection->isTableExists($table)) {
            throw new \Exception(sprintf('Table `%s` not found!', $table));
        }

        $columns = [];
        $description = $this->connection->describeTable($table);
        foreach ($description as $columnDescription) {
            $columns[$columnDescription['COLUMN_NAME']] = $this->connection->getColumnDefinitionFromDescribe(
                $columnDescription
            );
        }

        if (!isset($columns[$column])) {
            throw new \Exception(sprintf('Column `%s` not found in table `%s`!', $column, $table));
        } elseif ($after && !isset($columns[$after])) {
            throw new \Exception(sprintf('Positioning column `%s` not found in table `%s`!', $after, $table));
        }

        if ($after) {
            $sql = sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s AFTER %s',
                $this->connection->quoteIdentifier($table),
                $this->connection->quoteIdentifier($column),
                $columns[$column],
                $this->connection->quoteIdentifier($after)
            );
        } else {
            $sql = sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s FIRST',
                $this->connection->quoteIdentifier($table),
                $this->connection->quoteIdentifier($column),
                $columns[$column]
            );
        }

        $this->connection->query($sql);

        return $this;
    }
}
