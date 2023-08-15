<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ResourceConnections\DB\Adapter\Pdo;

use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;

// @codingStandardsIgnoreStart

/**
 * Proxy for MySQL database adapter
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @api
 * @since 100.0.2
 */
class MysqlProxy extends Mysql
// @codingStandardsIgnoreEnd
{
    public const CONFIG_MAX_ALLOWED_LAG = 'maxAllowedLag';

    /**
     * @var bool
     */
    protected $masterConnectionOnly = false;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $masterConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $slaveConnection;

    /**
     * @var array
     */
    protected $masterConfig;

    /**
     * @var array
     */
    protected $slaveConfig;

    /**
     * All possible write statements
     * First 3 symbols for each statement
     *
     * @var string[]
     */
    protected $writeQueryPrefixes = ['del', 'upd', 'ins', 'loc', 'set'];

    /**
     * @var MysqlFactory
     * @since 100.2.0
     */
    protected $mysqlFactory;

    /**
     * @var bool
     */
    private bool $synchronousReplication = false;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param SelectFactory $selectFactory
     * @param array $config
     * @param MysqlFactory|null $mysqlFactory
     */
    public function __construct(
        LoggerInterface $logger,
        SelectFactory $selectFactory,
        array $config,
        MysqlFactory $mysqlFactory = null
    ) {
        $this->logger = $logger;
        $this->selectFactory = $selectFactory;
        $this->mysqlFactory = $mysqlFactory ?: ObjectManager::getInstance()->get(MysqlFactory::class);
        if (!isset($config['slave'])) {
            $this->masterConfig = $config;
            $this->setUseMasterConnection();
        } else {
            $this->masterConfig = $config;
            unset($this->masterConfig['slave']);
            $this->slaveConfig = array_merge($this->masterConfig, $config['slave']);
            $this->synchronousReplication = $this->slaveConfig['synchronous_replication'] ?? false;
        }
    }

    /**
     * Set master connection
     *
     * @return $this
     */
    public function setUseMasterConnection()
    {
        $this->masterConnectionOnly = true;
        return $this;
    }

    /**
     * Return master connection
     *
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getMasterConnection()
    {
        if (!isset($this->masterConnection)) {
            $this->masterConnection = $this->mysqlFactory->create(
                Mysql::class,
                $this->masterConfig,
                $this->logger,
                $this->selectFactory
            );
        }
        return $this->masterConnection;
    }

    /**
     * Return slave connection
     *
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getSlaveConnection()
    {
        if (!isset($this->slaveConnection)) {
            $this->slaveConnection = $this->mysqlFactory->create(
                Mysql::class,
                $this->slaveConfig,
                $this->logger,
                $this->selectFactory
            );
            if (!empty($this->slaveConfig[self::CONFIG_MAX_ALLOWED_LAG])) {
                $maxLag = (float)$this->slaveConfig[self::CONFIG_MAX_ALLOWED_LAG];
                $slaveStatus = $this->slaveConnection->fetchRow('SHOW SLAVE STATUS');
                if (!empty($slaveStatus['Seconds_Behind_Master'])
                    && (float)$slaveStatus['Seconds_Behind_Master'] >= $maxLag
                ) {
                    unset($this->slaveConnection);
                    $this->setUseMasterConnection();
                    return $this->getMasterConnection();
                }
            }
        }
        return $this->slaveConnection;
    }

    /**
     * Check that this is read only query
     *
     * @param string|\Magento\Framework\DB\Select $sql The SQL statement with placeholders.
     * @return bool
     */
    protected function isReadOnlyQuery($sql)
    {
        $sql = $sql !== null ? ltrim(preg_replace('/\s+/', ' ', $sql)) : '';
        $sqlMessage = explode(' ', $sql, 3);
        $startSql = strtolower(substr($sqlMessage[0], 0, 3));
        return
            !in_array($startSql, $this->writeQueryPrefixes) &&
            !in_array($startSql, $this->_ddlRoutines) &&
            !preg_match('/(GET_LOCK|RELEASE_LOCK|IS_USED_LOCK)/i', $sql);
    }

    /**
     * Select defined connection
     *
     * @param string|\Magento\Framework\DB\Select $sql The SQL statement with placeholders.
     *
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function selectConnection($sql = null)
    {
        if (!$this->masterConnectionOnly &&
            ($sql === null || $this->isReadOnlyQuery($sql) || $this->synchronousReplication === true)
        ) {
            return $this->getSlaveConnection();
        }

        if ($this->synchronousReplication === false) {
            $this->setUseMasterConnection();
        }

        return $this->getMasterConnection();
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->beginTransaction();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->commit();
        if ($this->synchronousReplication
            && $this->getMasterConnection()->getTransactionLevel() === 0) {
            $this->masterConnectionOnly = false;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rollBack()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->rollBack();
        if ($this->synchronousReplication
            && $this->getMasterConnection()->getTransactionLevel() === 0) {
            $this->masterConnectionOnly = false;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTransactionLevel()
    {
        return $this->getMasterConnection()->getTransactionLevel();
    }

    /**
     * @inheritDoc
     */
    public function convertDate($date)
    {
        return $this->selectConnection()->convertDate($date);
    }

    /**
     * @inheritDoc
     */
    public function convertDateTime($datetime)
    {
        return $this->selectConnection()->convertDateTime($datetime);
    }

    /**
     * @inheritDoc
     */
    public function rawQuery($sql)
    {
        return $this->selectConnection($sql)->rawQuery($sql);
    }

    /**
     * @inheritDoc
     */
    public function rawFetchRow($sql, $field = null)
    {
        return $this->selectConnection($sql)->rawFetchRow($sql, $field);
    }

    /**
     * @inheritDoc
     */
    public function query($sql, $bind = [])
    {
        return $this->selectConnection($sql)->query($sql, $bind);
    }

    /**
     * @inheritDoc
     */
    public function multiQuery($sql, $bind = [])
    {
        return $this->selectConnection($sql)->multiQuery($sql, $bind);
    }

    /**
     * @inheritDoc
     */
    public function proccessBindCallback($matches)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->proccessBindCallback($matches);
    }

    /**
     * @inheritDoc
     */
    public function setQueryHook($hook)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->setQueryHook($hook);
    }

    /**
     * @inheritDoc
     */
    public function dropForeignKey($tableName, $fkName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->dropForeignKey($tableName, $fkName, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function purgeOrphanRecords(
        $tableName,
        $columnName,
        $refTableName,
        $refColumnName,
        $onDelete = AdapterInterface::FK_ACTION_CASCADE
    ) {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->purgeOrphanRecords(
            $tableName,
            $columnName,
            $refTableName,
            $refColumnName,
            $onDelete
        );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tableColumnExists($tableName, $columnName, $schemaName = null)
    {
        return $this->selectConnection()->tableColumnExists($tableName, $columnName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function addColumn($tableName, $columnName, $definition, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->addColumn($tableName, $columnName, $definition, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function dropColumn($tableName, $columnName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropColumn($tableName, $columnName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function changeColumn(
        $tableName,
        $oldColumnName,
        $newColumnName,
        $definition,
        $flushData = false,
        $schemaName = null
    ) {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->changeColumn(
            $tableName,
            $oldColumnName,
            $newColumnName,
            $definition,
            $flushData,
            $schemaName
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyColumn($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->modifyColumn($tableName, $columnName, $definition, $flushData, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function showTableStatus($tableName, $schemaName = null)
    {
        return $this->selectConnection()->showTableStatus($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function getCreateTable($tableName, $schemaName = null)
    {
        return $this->getMasterConnection()->getCreateTable($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function getForeignKeys($tableName, $schemaName = null)
    {
        return $this->getMasterConnection()->getForeignKeys($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function getForeignKeysTree()
    {
        return $this->getMasterConnection()->getForeignKeysTree();
    }

    /**
     * @inheritDoc
     */
    public function modifyTables($tables)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->modifyTables($tables);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIndexList($tableName, $schemaName = null)
    {
        return $this->selectConnection()->getIndexList($tableName, $schemaName);
    }

    /**
     * Creates and returns a new \Magento\Framework\DB\Select object for this adapter.
     *
     * @return Select
     */
    public function select()
    {
        return $this->selectFactory->create($this);
    }

    /**
     * @inheritDoc
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        return $this->selectConnection()->quoteInto($text, $value, $type, $count);
    }

    /**
     * @inheritDoc
     */
    public function loadDdlCache($tableCacheKey, $ddlType)
    {
        return $this->selectConnection()->loadDdlCache($tableCacheKey, $ddlType);
    }

    /**
     * @inheritDoc
     */
    public function saveDdlCache($tableCacheKey, $ddlType, $data)
    {
        $this->selectConnection()->saveDdlCache($tableCacheKey, $ddlType, $data);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resetDdlCache($tableName = null, $schemaName = null)
    {
        $this->selectConnection()->resetDdlCache($tableName, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disallowDdlCache()
    {
        $this->selectConnection()->disallowDdlCache();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function allowDdlCache()
    {
        $this->selectConnection()->allowDdlCache();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function describeTable($tableName, $schemaName = null)
    {
        return $this->selectConnection()->describeTable($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function getColumnCreateByDescribe($columnData)
    {
        return $this->selectConnection()->getColumnCreateByDescribe($columnData);
    }

    /**
     * @inheritDoc
     */
    public function createTableByDdl($tableName, $newTableName)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTableByDdl($tableName, $newTableName);
    }

    /**
     * @inheritDoc
     */
    public function modifyColumnByDdl($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->modifyColumnByDdl($tableName, $columnName, $definition, $flushData, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function changeTableEngine($tableName, $engine, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->changeTableEngine($tableName, $engine, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function changeTableComment($tableName, $comment, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->changeTableComment($tableName, $comment, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function insertForce($table, array $bind)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->insertForce($table, $bind);
    }

    /**
     * @inheritDoc
     */
    public function insertOnDuplicate($table, array $data, array $fields = [])
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->insertOnDuplicate($table, $data, $fields);
    }

    /**
     * @inheritDoc
     */
    public function insertMultiple($table, array $data)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->insertMultiple($table, $data);
    }

    /**
     * @inheritDoc
     */
    public function insertArray($table, array $columns, array $data, $strategy = 0)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->insertArray($table, $columns, $data, $strategy);
    }

    /**
     * @inheritDoc
     */
    public function setCacheAdapter(FrontendInterface $cacheAdapter)
    {
        $this->selectConnection()->setCacheAdapter($cacheAdapter);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function newTable($tableName = null, $schemaName = null)
    {
        return $this->getMasterConnection()->newTable($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function createTable(Table $table)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTable($table);
    }

    /**
     * @inheritDoc
     */
    public function createTemporaryTable(\Magento\Framework\DB\Ddl\Table $table)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTemporaryTable($table);
    }

    /**
     * @inheritDoc
     */
    public function createTemporaryTableLike($temporaryTableName, $originTableName, $ifNotExists = false)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTemporaryTableLike(
            $temporaryTableName,
            $originTableName,
            $ifNotExists
        );
    }

    /**
     * @inheritDoc
     */
    public function renameTablesBatch(array $tablePairs)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->renameTablesBatch($tablePairs);
    }

    /**
     * @inheritDoc
     */
    public function getColumnDefinitionFromDescribe($options, $ddlType = null)
    {
        return $this->selectConnection()->getColumnDefinitionFromDescribe($options, $ddlType);
    }

    /**
     * @inheritDoc
     */
    public function dropTable($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropTable($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function dropTemporaryTable($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropTemporaryTable($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function truncateTable($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->truncateTable($tableName, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isTableExists($tableName, $schemaName = null)
    {
        return $this->selectConnection()->isTableExists($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function renameTable($oldTableName, $newTableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->renameTable($oldTableName, $newTableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function addIndex(
        $tableName,
        $indexName,
        $fields,
        $indexType = AdapterInterface::INDEX_TYPE_INDEX,
        $schemaName = null
    ) {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->addIndex($tableName, $indexName, $fields, $indexType, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function dropIndex($tableName, $keyName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropIndex($tableName, $keyName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function addForeignKey(
        $fkName,
        $tableName,
        $columnName,
        $refTableName,
        $refColumnName,
        $onDelete = AdapterInterface::FK_ACTION_CASCADE,
        $purge = false,
        $schemaName = null,
        $refSchemaName = null
    ) {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->addForeignKey(
            $fkName,
            $tableName,
            $columnName,
            $refTableName,
            $refColumnName,
            $onDelete,
            $purge,
            $schemaName,
            $refSchemaName
        );
    }

    /**
     * @inheritDoc
     */
    public function formatDate($date, $includeTime = true)
    {
        return $this->selectConnection()->formatDate($date, $includeTime);
    }

    /**
     * @inheritDoc
     */
    public function startSetup()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->startSetup();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function endSetup()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->endSetup();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function prepareSqlCondition($fieldName, $condition)
    {
        return $this->selectConnection()->prepareSqlCondition($fieldName, $condition);
    }

    /**
     * @inheritDoc
     */
    public function prepareColumnValue(array $column, $value)
    {
        return $this->selectConnection()->prepareColumnValue($column, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCheckSql($expression, $true, $false)
    {
        return $this->selectConnection()->getCheckSql($expression, $true, $false);
    }

    /**
     * @inheritDoc
     */
    public function getIfNullSql($expression, $value = 0)
    {
        return $this->selectConnection()->getIfNullSql($expression, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCaseSql($valueName, $casesResults, $defaultValue = null)
    {
        return $this->selectConnection()->getCaseSql($valueName, $casesResults, $defaultValue);
    }

    /**
     * @inheritDoc
     */
    public function getConcatSql(array $data, $separator = null)
    {
        return $this->selectConnection()->getConcatSql($data, $separator);
    }

    /**
     * @inheritDoc
     */
    public function getLengthSql($string)
    {
        return $this->selectConnection()->getLengthSql($string);
    }

    /**
     * @inheritDoc
     */
    public function getLeastSql(array $data)
    {
        return $this->selectConnection()->getLeastSql($data);
    }

    /**
     * @inheritDoc
     */
    public function getGreatestSql(array $data)
    {
        return $this->selectConnection()->getGreatestSql($data);
    }

    /**
     * @inheritDoc
     */
    public function getDateAddSql($date, $interval, $unit)
    {
        return $this->selectConnection()->getDateAddSql($date, $interval, $unit);
    }

    /**
     * @inheritDoc
     */
    public function getDateSubSql($date, $interval, $unit)
    {
        return $this->selectConnection()->getDateSubSql($date, $interval, $unit);
    }

    /**
     * @inheritDoc
     */
    public function getDateFormatSql($date, $format)
    {
        return $this->selectConnection()->getDateFormatSql($date, $format);
    }

    /**
     * @inheritDoc
     */
    public function getDatePartSql($date)
    {
        return $this->selectConnection()->getDatePartSql($date);
    }

    /**
     * @inheritDoc
     */
    public function getSubstringSql($stringExpression, $pos, $len = null)
    {
        return $this->selectConnection()->getSubstringSql($stringExpression, $pos, $len);
    }

    /**
     * @inheritDoc
     */
    public function getStandardDeviationSql($expressionField)
    {
        return $this->selectConnection()->getStandardDeviationSql($expressionField);
    }

    /**
     * @inheritDoc
     */
    public function getDateExtractSql($date, $unit)
    {
        return $this->selectConnection()->getDateExtractSql($date, $unit);
    }

    /**
     * @inheritDoc
     */
    public function getTableName($tableName)
    {
        return $this->selectConnection()->getTableName($tableName);
    }

    /**
     * @inheritDoc
     */
    public function getTriggerName($tableName, $time, $event)
    {
        return $this->selectConnection()->getTriggerName($tableName, $time, $event);
    }

    /**
     * @inheritDoc
     */
    public function getIndexName($tableName, $fields, $indexType = '')
    {
        return $this->selectConnection()->getIndexName($tableName, $fields, $indexType);
    }

    /**
     * @inheritDoc
     */
    public function getForeignKeyName($priTableName, $priColumnName, $refTableName, $refColumnName)
    {
        return $this->selectConnection()->getForeignKeyName(
            $priTableName,
            $priColumnName,
            $refTableName,
            $refColumnName
        );
    }

    /**
     * @inheritDoc
     */
    public function disableTableKeys($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->disableTableKeys($tableName, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function enableTableKeys($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->enableTableKeys($tableName, $schemaName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function insertFromSelect(Select $select, $table, array $fields = [], $mode = false)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->insertFromSelect($select, $table, $fields, $mode);
    }

    /**
     * @inheritDoc
     */
    public function selectsByRange($rangeField, \Magento\Framework\DB\Select $select, $stepCount = 100)
    {
        return $this->selectConnection()->selectsByRange($rangeField, $select, $stepCount);
    }

    /**
     * @inheritDoc
     */
    public function updateFromSelect(Select $select, $table)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->updateFromSelect($select, $table);
    }

    /**
     * @inheritDoc
     */
    public function deleteFromSelect(Select $select, $table)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->deleteFromSelect($select, $table);
    }

    /**
     * @inheritDoc
     */
    public function getTablesChecksum($tableNames, $schemaName = null)
    {
        return $this->selectConnection()->getTablesChecksum($tableNames, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function supportStraightJoin()
    {
        return $this->selectConnection()->supportStraightJoin();
    }

    /**
     * @inheritDoc
     */
    public function orderRand(Select $select, $field = null)
    {
        $this->selectConnection()->orderRand($select, $field);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function forUpdate($sql)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->forUpdate($sql);
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKeyName($tableName, $schemaName = null)
    {
        return $this->selectConnection()->getPrimaryKeyName($tableName, $schemaName);
    }

    /**
     * @inheritDoc
     */
    public function decodeVarbinary($value)
    {
        return $this->selectConnection()->decodeVarbinary($value);
    }

    /**
     * @inheritDoc
     */
    public function createTrigger(\Magento\Framework\DB\Ddl\Trigger $trigger)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTrigger($trigger);
    }

    /**
     * @inheritDoc
     */
    public function dropTrigger($triggerName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropTrigger($triggerName, $schemaName);
    }

    /**
     * Destroy connections with calling their destructor
     *
     * @return void
     */
    public function __destruct()
    {
        unset($this->slaveConnection);
        unset($this->masterConnection);
    }

    /**
     * @inheritDoc
     */
    public function getTables($likeCondition = null)
    {
        return $this->selectConnection()->getTables($likeCondition);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteIdentifierSymbol()
    {
        return $this->selectConnection()->getQuoteIdentifierSymbol();
    }

    /**
     * @inheritDoc
     */
    public function listTables()
    {
        return $this->selectConnection()->listTables();
    }

    /**
     * @inheritDoc
     */
    public function limit($sql, $count, $offset = 0)
    {
        return $this->selectConnection($sql)->limit($sql, $count, $offset);
    }

    /**
     * @inheritDoc
     */
    public function isConnected()
    {
        return $this->selectConnection()->isConnected();
    }

    /**
     * @inheritDoc
     */
    public function closeConnection()
    {
        $this->selectConnection()->closeConnection();
    }

    /**
     * @inheritDoc
     */
    public function prepare($sql)
    {
        return $this->selectConnection($sql)->prepare($sql);
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        return $this->getMasterConnection()->lastInsertId($tableName, $primaryKey);
    }

    /**
     * @inheritDoc
     */
    public function exec($sql)
    {
        return $this->selectConnection($sql)->exec($sql);
    }

    /**
     * @inheritDoc
     */
    public function setFetchMode($mode)
    {
        $this->selectConnection()->setFetchMode($mode);
    }

    /**
     * @inheritDoc
     */
    public function supportsParameters($type)
    {
        return $this->selectConnection()->supportsParameters($type);
    }

    /**
     * @inheritDoc
     */
    public function getServerVersion()
    {
        return $this->selectConnection()->getServerVersion();
    }

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->selectConnection()->getConnection();
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return $this->selectConnection()->getConfig();
    }

    /**
     * @inheritDoc
     */
    public function setProfiler($profiler)
    {
        if ($this->slaveConfig !== null) {
            $this->getSlaveConnection()->setProfiler($profiler);
        }
        return $this->getMasterConnection()->setProfiler($profiler);
    }

    /**
     * @inheritDoc
     */
    public function getProfiler()
    {
        return $this->selectConnection()->getProfiler();
    }

    /**
     * @inheritDoc
     */
    public function getStatementClass()
    {
        return $this->selectConnection()->getStatementClass();
    }

    /**
     * @inheritDoc
     */
    public function setStatementClass($class)
    {
        return $this->selectConnection()->setStatementClass($class);
    }

    /**
     * @inheritDoc
     */
    public function insert($table, array $bind)
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->insert($table, $bind);
    }

    /**
     * @inheritDoc
     */
    public function update($table, array $bind, $where = '')
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->update($table, $bind, $where);
    }

    /**
     * @inheritDoc
     */
    public function delete($table, $where = '')
    {
        if ($this->synchronousReplication !== true) {
            $this->setUseMasterConnection();
        }
        return $this->getMasterConnection()->delete($table, $where);
    }

    /**
     * @inheritDoc
     */
    public function getFetchMode()
    {
        return $this->selectConnection()->getFetchMode();
    }

    /**
     * @inheritDoc
     */
    public function fetchAll($sql, $bind = [], $fetchMode = null)
    {
        return $this->selectConnection()->fetchAll($sql, $bind, $fetchMode);
    }

    /**
     * @inheritDoc
     */
    public function fetchRow($sql, $bind = [], $fetchMode = null)
    {
        return $this->selectConnection()->fetchRow($sql, $bind, $fetchMode);
    }

    /**
     * @inheritDoc
     */
    public function fetchAssoc($sql, $bind = [])
    {
        return $this->selectConnection()->fetchAssoc($sql, $bind);
    }

    /**
     * @inheritDoc
     */
    public function fetchCol($sql, $bind = [])
    {
        return $this->selectConnection()->fetchCol($sql, $bind);
    }

    /**
     * @inheritDoc
     */
    public function fetchPairs($sql, $bind = [])
    {
        return $this->selectConnection()->fetchPairs($sql, $bind);
    }

    /**
     * @inheritDoc
     */
    public function fetchOne($sql, $bind = [])
    {
        return $this->selectConnection()->fetchOne($sql, $bind);
    }

    /**
     * @inheritDoc
     */
    public function quote($value, $type = null)
    {
        return $this->selectConnection()->quote($value, $type);
    }

    /**
     * @inheritDoc
     */
    public function quoteIdentifier($ident, $auto = false)
    {
        return $this->selectConnection()->quoteIdentifier($ident, $auto);
    }

    /**
     * @inheritDoc
     */
    public function quoteColumnAs($ident, $alias, $auto = false)
    {
        return $this->selectConnection()->quoteColumnAs($ident, $alias, $auto);
    }

    /**
     * @inheritDoc
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
        return $this->selectConnection()->quoteTableAs($ident, $alias, $auto);
    }

    /**
     * @inheritDoc
     */
    public function lastSequenceId($sequenceName)
    {
        return $this->getMasterConnection()->lastSequenceId($sequenceName);
    }

    /**
     * @inheritDoc
     */
    public function nextSequenceId($sequenceName)
    {
        return $this->getMasterConnection()->nextSequenceId($sequenceName);
    }

    /**
     * @inheritDoc
     */
    public function foldCase($key)
    {
        return $this->selectConnection()->foldCase($key);
    }

    /**
     * @inheritDoc
     * @since 100.1.0
     */
    public function getAutoIncrementField($tableName, $schemaName = null)
    {
        $indexName = $this->getMasterConnection()->getPrimaryKeyName($tableName, $schemaName);
        $indexes = $this->getMasterConnection()->getIndexList($tableName);
        if ($indexName && isset($indexes[$indexName]) && count($indexes[$indexName]['COLUMNS_LIST']) == 1) {
            return current($indexes[$indexName]['COLUMNS_LIST']);
        }
        return false;
    }

    /**
     * @inheritDoc
     * @since 100.3.0
     */
    public function getSchemaListener() //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    {
        return parent::getSchemaListener();
    }

    /**
     * @inheritDoc
     */
    public function __sleep() //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    { /** @phpstan-ignore-line */ /** phpcs:ignore */
    }

    /**
     * @inheritDoc
     */
    public function __wakeup() //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }

    /**
     * @inheritDoc
     */
    protected function _connect() //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }
}
