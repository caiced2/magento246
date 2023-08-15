<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Sequence as DdlSequence;
use Magento\Framework\DB\Ddl\Table;
use Magento\Staging\Model\VersionManager;

/**
 * @codeCoverageIgnore
 */
class BasicSetup
{
    /**
     * @var SchemaSetupInterface
     */
    protected $setup;

    /**
     * @var DdlSequence
     */
    protected $ddlSequence;

    /**
     * @var string
     */
    protected $sequenceTableName;

    /**
     * @var string
     */
    protected $stagedTable;

    /**
     * @var string
     */
    protected $entityColumn;

    /**
     * @var array
     */
    protected $foreignKeys;

    /**
     * @var array
     */
    protected $tableDescription = [];

    /**
     * @param DdlSequence $ddlSequence
     */
    public function __construct(DdlSequence $ddlSequence)
    {
        $this->ddlSequence = $ddlSequence;
    }

    /**
     * Install staging for a given table
     *
     * @param SchemaSetupInterface $setup
     * @param string $sequenceTableName
     * @param string $stagedTable
     * @param string $entityColumn
     * @param array $foreignKeys
     * @return void
     */
    public function install(
        SchemaSetupInterface $setup,
        $sequenceTableName,
        $stagedTable,
        $entityColumn,
        $foreignKeys
    ) {
        $this->setup = $setup;
        $this->sequenceTableName = $sequenceTableName;
        $this->stagedTable = $stagedTable;
        $this->entityColumn = $entityColumn;
        $this->foreignKeys = $foreignKeys;

        $this->execute();
    }

    /**
     * Install staging for a given table
     *
     * @return void
     */
    protected function execute()
    {
        $this->createSequenceTable();
        $this->dropForeignKey();
        $this->changeEntityColumn($this->stagedTable, $this->entityColumn);
        $this->addRequiredColumns($this->stagedTable, $this->entityColumn);
        $this->updateReferenceTables();
        $this->addForeignKeys();
    }

    /**
     * Rename entity column in reference tables
     *
     * @return void
     */
    protected function updateReferenceTables()
    {
        foreach ($this->foreignKeys as $foreignKey) {
            if (!$foreignKey['staged']) {
                continue;
            }
            $this->setup->getConnection()
                ->changeColumn(
                    $this->setup->getTable($foreignKey['referenceTable']),
                    $foreignKey['referenceColumn'],
                    'row_id',
                    [
                        'nullable' => false,
                        'type' => $this->getEntityColumnType(),
                        'unsigned' => $this->isEntityColumnUnsigned(),
                        'comment' => 'Version Id',
                    ],
                    true
                );
        }
    }

    /**
     * Create sequence table
     *
     * @return void
     */
    protected function createSequenceTable()
    {
        $table = $this->setup->getConnection()
            ->newTable($this->setup->getTable($this->sequenceTableName))
            ->addColumn(
                'sequence_value',
                $this->getEntityColumnType(),
                null,
                [
                    'nullable' => false,
                    'unsigned' => $this->isEntityColumnUnsigned(),
                    'identity' => true,
                    'primary' => true
                ]
            );

        $this->setup->getConnection()
            ->createTable($table);
        $select = $this->setup->getConnection()
            ->select()
            ->from($this->setup->getTable($this->stagedTable), [$this->entityColumn]);
        $this->setup->getConnection()->query(
            $this->setup->getConnection()->insertFromSelect(
                $select,
                $this->setup->getTable($this->sequenceTableName),
                ['sequence_value']
            )
        );
    }

    /**
     * Retrieve entity column type
     *
     * @return string
     */
    protected function getEntityColumnType()
    {
        $columnDefinition = $this->setup->getConnection()
            ->getColumnCreateByDescribe(
                $this->getStagedTableDescription()[$this->entityColumn]
            );
        return $columnDefinition['type'];
    }

    /**
     * Is entity column unsigned
     *
     * @return mixed
     */
    protected function isEntityColumnUnsigned()
    {
        return $this->getStagedTableDescription()[$this->entityColumn]['UNSIGNED'];
    }

    /**
     * Retrieve staged table description
     *
     * @return array
     */
    protected function getStagedTableDescription()
    {
        if (!isset($this->tableDescription[$this->stagedTable])) {
            $this->tableDescription[$this->stagedTable] = $this->setup->getConnection()
                ->describeTable($this->setup->getTable($this->stagedTable));
        }
        return $this->tableDescription[$this->stagedTable];
    }

    /**
     * Add foreign keys
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey($this->stagedTable, $this->entityColumn, $this->sequenceTableName, 'sequence_value');
        foreach ($this->foreignKeys as $foreignKey) {
            $referenceTable = $foreignKey['referenceTable'];
            $referenceColumn = $foreignKey['referenceColumn'];
            $staged = $foreignKey['staged'];

            $targetTable = $this->sequenceTableName;
            $targetColumn = 'sequence_value';
            if ($staged) {
                $targetTable = $this->stagedTable;
                $targetColumn = 'row_id';
                $referenceColumn = 'row_id';
            }
            $this->addForeignKey($referenceTable, $referenceColumn, $targetTable, $targetColumn);
        }
    }

    /**
     * Drop foreign keys
     *
     * @return void
     */
    protected function dropForeignKey()
    {
        foreach ($this->foreignKeys as $tableInfo) {
            $this->setup->getConnection()->dropForeignKey(
                $this->setup->getTable($tableInfo['referenceTable']),
                $this->setup->getFkName(
                    $tableInfo['referenceTable'],
                    $tableInfo['referenceColumn'],
                    $this->stagedTable,
                    $this->entityColumn
                )
            );
        }
    }

    /**
     * Add row id into staged table and copy value from row_id to entity column
     *
     * @param string $table
     * @param string $column
     * @return void
     */
    protected function changeEntityColumn($table, $column)
    {
        $this->setup->getConnection()
            ->changeColumn(
                $this->setup->getTable($table),
                $column,
                'row_id',
                [
                    'type' => $this->getEntityColumnType(),
                    'identity' => true,
                    'unsigned' => $this->isEntityColumnUnsigned(),
                    'nullable' => false,
                    'primary' => true,
                    'comment' => 'Version Id',
                ],
                true
            );
        $this->setup->getConnection()
            ->addColumn(
                $this->setup->getTable($table),
                $column,
                [
                    'type' => $this->getEntityColumnType(),
                    'unsigned' => $this->isEntityColumnUnsigned(),
                    'nullable' => false,
                    'after' => 'row_id',
                    'comment' => 'Entity Id',
                ]
            );
        $this->setup->getConnection()->update(
            $this->setup->getTable($table),
            [$column => new \Zend_Db_Expr('row_id')]
        );
    }

    /**
     * Add created_in updated_in columns
     *
     * @param string $table
     * @param string $entityColumn
     * @return void
     */
    protected function addRequiredColumns($table, $entityColumn)
    {
        $this->setup->getConnection()
            ->addColumn(
                $this->setup->getTable($table),
                'created_in',
                [
                    'type' => Table::TYPE_BIGINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'after' => $entityColumn,
                    'comment' => 'Update Id',
                ]
            );
        $this->setup->getConnection()
            ->addColumn(
                $this->setup->getTable($table),
                'updated_in',
                [
                    'type' => Table::TYPE_BIGINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'after' => 'created_in',
                    'comment' => 'Next Update Id',
                ]
            );
        $this->setup->getConnection()->update(
            $this->setup->getTable($table),
            ['created_in' => 1]
        );
        $this->setup->getConnection()->update(
            $this->setup->getTable($table),
            ['updated_in' => VersionManager::MAX_VERSION]
        );
        $this->setup->getConnection()
            ->addIndex(
                $this->setup->getTable($table),
                $this->setup->getIdxName($this->setup->getTable($table), ['created_in']),
                'created_in'
            );
        $this->setup->getConnection()
            ->addIndex(
                $this->setup->getTable($table),
                $this->setup->getIdxName($this->setup->getTable($table), ['updated_in']),
                'updated_in'
            );
    }

    /**
     * Add foreign key
     *
     * @param string $targetTable
     * @param string $targetColumn
     * @param string $referenceTable
     * @param string $referenceColumn
     * @return void
     */
    protected function addForeignKey($targetTable, $targetColumn, $referenceTable, $referenceColumn)
    {
        $this->setup->getConnection()->addForeignKey(
            $this->setup->getFkName(
                $targetTable,
                $targetColumn,
                $referenceTable,
                $referenceColumn
            ),
            $this->setup->getTable($targetTable),
            $targetColumn,
            $this->setup->getTable($referenceTable),
            $referenceColumn
        );
    }
}
