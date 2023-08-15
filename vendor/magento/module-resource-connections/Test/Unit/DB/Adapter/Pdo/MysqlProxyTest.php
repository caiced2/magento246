<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ResourceConnections\Test\Unit\DB\Adapter\Pdo;

use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\Profiler;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MysqlProxyTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var SelectFactory|MockObject
     */
    private $selectFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var \Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy
     */
    private $mysqlProxy;

    /**
     * @var array
     */
    private $config = [
        'host' => 'slaveHost',
        'active' => true,
        'initStatements' => 'SET NAMES utf8',
        'type' => 'pdo_mysql',
        'slave' => [
            'host' => 'slaveHost'
        ]
    ];

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|MockObject
     */
    private $masterConnectionMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|MockObject
     */
    private $slaveConnectionMock;

    /**
     * @var MysqlFactory|MockObject
     */
    private $mysqlFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->masterConnectionMock = $this->createMock(Mysql::class);
        $this->slaveConnectionMock = $this->createMock(Mysql::class);
        $this->selectFactoryMock = $this->createMock(SelectFactory::class);
        $this->mysqlFactoryMock = $this->createMock(MysqlFactory::class);
        $this->mysqlProxy = $this->objectManager->getObject(
            MysqlProxy::class,
            [
                'logger' => $this->loggerMock,
                'selectFactory' => $this->selectFactoryMock,
                'config' => $this->config,
                'mysqlFactory' => $this->mysqlFactoryMock,
            ]
        );
    }

    /**
     * @param string $methodName
     * @param array $params
     *
     * @return void
     * @dataProvider switchToMasterMethodsDataProvider
     */
    public function testPermanentlySwitchToMaster(string $methodName, array $params): void
    {
        $expectedBuilderConfig = $this->config;
        unset($expectedBuilderConfig['slave']);
        $this->mysqlFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                Mysql::class,
                $expectedBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->masterConnectionMock);
        $this->masterConnectionMock->expects($this->once())->method($methodName);
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        $this->masterConnectionMock->expects($this->once())->method('rawQuery')->with('SOME QUERY');
        $this->mysqlProxy->rawQuery('SOME QUERY');
    }

    /**
     * @return array
     */
    public function switchToMasterMethodsDataProvider(): array
    {
        return [
            ['beginTransaction', []],
            ['commit', []],
            ['rollBack', []],
            ['proccessBindCallback', ['matches']],
            ['setQueryHook', ['hook']],
            ['dropForeignKey', [null, null, null]],
            ['purgeOrphanRecords', [null, null, null, null]],
            ['addColumn', [null, null, null]],
            ['dropColumn', [null, null]],
            ['changeColumn', [null, null, null, null]],
            ['modifyColumn', [null, null, null]],
            ['modifyTables', [null]],
            ['createTableByDdl', [null, null]],
            ['modifyColumnByDdl', [null, null, null]],
            ['changeTableEngine', [null, null]],
            ['changeTableComment', [null, null]],
            ['insertForce', [null, []]],
            ['insertOnDuplicate', [null, []]],
            ['insertMultiple', [null, []]],
            ['insertArray', [null, [], []]],
            ['createTable', [$this->createMock(Table::class)]],
            ['createTemporaryTable', [$this->createMock(Table::class)]],
            ['createTemporaryTableLike', ['tempTable', 'origin']],
            ['renameTablesBatch', [['table1', 'table2']]],
            ['dropTable', [null]],
            ['dropTemporaryTable', [null]],
            ['truncateTable', [null]],
            ['renameTable', [null, null]],
            ['addIndex', [null, null, null]],
            ['dropIndex', [null, null]],
            ['addForeignKey', [null, null, null, null, null]],
            ['startSetup', []],
            ['endSetup', []],
            ['disableTableKeys', ['table']],
            ['enableTableKeys', ['table']],
            ['insertFromSelect', [$this->createMock(Select::class), 'table']],

            ['updateFromSelect', [$this->createMock(Select::class), 'table']],
            ['deleteFromSelect', [$this->createMock(Select::class), 'table']],
            ['forUpdate', ['SOME QUERY']],
            ['createTrigger', [$this->createMock(Trigger::class)]],
            ['dropTrigger', ['triggerName']],
            ['insert', ['table', []]],
            ['update', ['table', [], '']],
            ['delete', ['table', []]],
        ];
    }

    /**
     * @param string $methodName
     * @param array $params
     *
     * @return void
     * @dataProvider passToMasterOnceDataProvider
     */
    public function testPassToMasterOnce(string $methodName, array $params): void
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);

        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        $this->mysqlFactoryMock
            ->method('create')
            ->withConsecutive(
                [Mysql::class, $expectedMasterBuilderConfig, $this->loggerMock],
                [Mysql::class, $expectedSlaveBuilderConfig, $this->loggerMock]
            )
            ->willReturnOnConsecutiveCalls(
                $this->masterConnectionMock,
                $this->slaveConnectionMock
            );

        $this->masterConnectionMock->expects($this->once())->method($methodName);
        $this->slaveConnectionMock->method('rawQuery')->with('SOME QUERY');
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        $this->mysqlProxy->rawQuery('SOME QUERY');
    }

    /**
     * @return array
     */
    public function passToMasterOnceDataProvider(): array
    {
        return [
            ['getTransactionLevel', []],
            ['getCreateTable', ['table']],
            ['getForeignKeys', ['table']],
            ['getForeignKeysTree', []],
            ['getIndexList', ['table']],
            ['describeTable', ['table']],
            ['getColumnCreateByDescribe', ['coldata']],
            ['newTable', []],
            ['getColumnDefinitionFromDescribe', ['options']],
        ];
    }

    /**
     * @param string $methodName
     * @param array $params
     *
     * @return void
     * @dataProvider selectConnectionSwitchingDataProvider
     */
    public function testSelectConnectionSwitching(string $methodName, array $params): void
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        $this->mysqlFactoryMock
            ->method('create')
            ->withConsecutive(
                [Mysql::class, $expectedSlaveBuilderConfig, $this->loggerMock],
                [Mysql::class, $expectedMasterBuilderConfig, $this->loggerMock]
            )
            ->willReturnOnConsecutiveCalls(
                $this->slaveConnectionMock,
                $this->masterConnectionMock
            );

        $this->masterConnectionMock->expects($this->once())->method($methodName);
        $this->slaveConnectionMock->expects($this->exactly(2))->method($methodName);
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        $this->mysqlProxy->setUseMasterConnection();
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
    }

    /**
     * @param string $methodName
     * @param string $param
     *
     * @return void
     * @dataProvider onlyMasterProvider
     */
    public function testMasterOnlyConnectionMethods(string $methodName, string $param): void
    {
        $mysqlProxy = $this->objectManager->getObject(
            MysqlProxy::class,
            [
                'logger' => $this->loggerMock,
                'selectFactory' => $this->selectFactoryMock,
                'config' => $this->config,
                'mysqlFactory' => $this->mysqlFactoryMock,
                'masterConnection' => $this->masterConnectionMock,
                'slaveConnection' => $this->slaveConnectionMock,
            ]
        );

        $this->masterConnectionMock->expects($this->once())->method($methodName);
        call_user_func_array([$mysqlProxy, $methodName], [$param]);
    }

    /**
     * @return array
     */
    public function onlyMasterProvider()
    {
        return [
            ['lastInsertId', ''],
            ['nextSequenceId', 'sequence'],
            ['lastSequenceId', 'sequence'],
        ];
    }

    /**
     * @return array
     */
    public function selectConnectionSwitchingDataProvider(): array
    {
        return [
            ['getFetchMode', []],
            ['convertDate', ['2015-01-09']],
            ['convertDateTime', ['2015-01-01 12:22:33']],
            ['rawQuery', ['SOME QUERY']],
            ['rawFetchRow', ['SOME QUERY']],
            ['query', ['SOME QUERY']],
            ['multiQuery', ['MULTIQUERY']],
            ['tableColumnExists', ['table', 'col']],
            ['showTableStatus', ['table']],
            ['quoteInto', ['text', 'val']],
            ['loadDdlCache', ['cacheKey', 'ddltype']],
            ['saveDdlCache', ['cacheKey', 'ddltype', 'data']],
            ['resetDdlCache', []],
            ['disallowDdlCache', []],
            ['allowDdlCache', []],
            ['setCacheAdapter', [$this->getMockForAbstractClass(FrontendInterface::class)]],
            ['isTableExists', ['table']],
            ['formatDate', ['2015-10-12']],
            ['prepareSqlCondition', ['field', 'cond']],
            ['prepareColumnValue', [[1, 2, 3], 'value']],
            ['getCheckSql', ['expr', 'true', 'false']],
            ['getIfNullSql', ['expr', 'value']],
            ['getCaseSql', ['valueName', 'cases', 'default']],
            ['getConcatSql', [[]]],
            ['getLengthSql', ['string']],
            ['getLeastSql', [[]]],
            ['getGreatestSql', [[]]],
            ['getDateAddSql', ['date', 'interval', 'unit']],
            ['getDateSubSql', ['date', 'interval', 'unit']],
            ['getDateFormatSql', ['date', 'format']],
            ['getDatePartSql', ['date']],
            ['getSubstringSql', ['stringExpr', 'pos']],
            ['getStandardDeviationSql', ['expr']],
            ['getDateExtractSql', ['date', 'unit']],
            ['getTableName', ['table']],
            ['getTriggerName', ['trigger', 'time', 'event']],
            ['getIndexName', ['index', 'fields']],
            ['getForeignKeyName', ['primTable', 'priCol', 'refTable', 'refcol']],
            ['selectsByRange', [
                'rangeField',
                $this->createMock(Select::class),
                100
            ]],
            ['getTablesChecksum', ['table1, table2']],
            ['supportStraightJoin', []],
            ['orderRand', [$this->createMock(Select::class), 'field']],
            ['getPrimaryKeyName', ['table']],
            ['decodeVarbinary', ['value']],
            ['getTables', []],
            ['getQuoteIdentifierSymbol', []],
            ['listTables', []],
            ['limit', ['sql', 'count', 'offset']],
            ['isConnected', []],
            ['closeConnection', []],
            ['prepare', ['sql']],
            ['exec', ['sql']],
            ['setFetchMode', ['mode']],
            ['supportsParameters', ['type']],
            ['getServerVersion', []],
            ['getConnection', []],
            ['getConfig', []],
            ['getProfiler', []],
            ['getStatementClass', []],
            ['setStatementClass', ['class']],
            ['getFetchMode', []],
            ['fetchAll', ['sql']],
            ['fetchRow', ['sql']],
            ['fetchAssoc', ['sql']],
            ['fetchCol', ['sql']],
            ['fetchPairs', ['sql']],
            ['fetchOne', ['sql']],
            ['quote', ['value']],
            ['quoteIdentifier', ['identifier']],
            ['quoteColumnAs', ['table', 'alias']],
            ['quoteTableAs', ['ident']],
        ];
    }

    /**
     * @param array $sqls
     * @param string $sequence
     *
     * @return void
     * @dataProvider selectConnectionSwitchingByQueryDataProvider
     */
    public function testSelectConnectionSwitchingByQuery(array $sqls, string $sequence): void
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);
        $withArgs = $willReturnArgs = [];

        if (strpos($sequence, 's') !== false) {
            $withArgs[] = [Mysql::class, $expectedSlaveBuilderConfig, $this->loggerMock];
            $willReturnArgs[] = $this->slaveConnectionMock;
        }

        if (strpos($sequence, 'm') !== false) {
            $withArgs[] = [Mysql::class, $expectedMasterBuilderConfig, $this->loggerMock];
            $willReturnArgs[] = $this->masterConnectionMock;
        }

        if ($sequence[0] == 'm') {
            $withArgs = array_reverse($withArgs);
            $willReturnArgs = array_reverse($willReturnArgs);
        }

        $this->mysqlFactoryMock
            ->method('create')
            ->withConsecutive(...$withArgs)
            ->willReturnOnConsecutiveCalls(...$willReturnArgs);

        $masterCalls = substr_count($sequence, 'm');
        $slaveCalls = substr_count($sequence, 's');
        $this->masterConnectionMock->expects($this->exactly($masterCalls))->method('exec');
        $this->slaveConnectionMock->expects($this->exactly($slaveCalls))->method('exec');

        foreach ($sqls as $query) {
            $this->mysqlProxy->exec($query);
        }
    }

    /**
     * @return array
     */
    public function selectConnectionSwitchingByQueryDataProvider(): array
    {
        return [
            [
                ['SELECT * FROM table WHERE 1'],
                's'
            ],
            [
                [
                    'SELECT * FROM table WHERE 1',
                    'SELECT * FROM xxx',
                    'SELECT * FROM xxx',
                    'SELECT * FROM xxx',
                    'SELECT * FROM xxx',
                    'DROP TABLE `xxx`'
                ],
                'sssssm'
            ],
            [
                ['SELECT * FROM table WHERE 1', 'CREATE TABLE `xxx`', 'SELECT * FROM xxx'],
                'smm'
            ],
            [
                ['CREATE TABLE `xxx`', 'SELECT * FROM xxx', 'SELECT * FROM table WHERE 1'],
                'mmm'
            ],
            [
                [
                    'DELETE FROM xxx',
                    'SELECT * FROM xxx',
                    'SELECT * FROM table WHERE 1'],
                'mmm'
            ]
        ];
    }

    /**
     * @return void
     */
    public function testSetProfiler(): void
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        $this->mysqlFactoryMock
            ->method('create')
            ->withConsecutive(
                [Mysql::class, $expectedSlaveBuilderConfig, $this->loggerMock],
                [Mysql::class, $expectedMasterBuilderConfig, $this->loggerMock]
            )
            ->willReturnOnConsecutiveCalls(
                $this->slaveConnectionMock,
                $this->masterConnectionMock
            );

        /** @var Profiler $profilerMock */
        $profilerMock = $this->createMock(Profiler::class);

        $this->masterConnectionMock->expects($this->once())->method('setProfiler')->with($profilerMock);
        $this->slaveConnectionMock->expects($this->once())->method('setProfiler')->with($profilerMock);
        $this->mysqlProxy->setProfiler($profilerMock);
    }

    /**
     * @return void
     */
    public function testMethodsList(): void
    {
        $mysqlClassName = Mysql::class;
        $mysqlClass = new \ReflectionClass($mysqlClassName);
        $mysqlMethodsList = $mysqlClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($mysqlMethodsList as $key => $value) {
            $mysqlMethodsList[$key] = $value->name;
        }

        $mysqlProxyClassName = MysqlProxy::class;
        $mysqlProxyClass = new \ReflectionClass($mysqlProxyClassName);

        foreach ($mysqlMethodsList as $mysqlMethod) {
            $this->assertEquals(
                $mysqlProxyClassName,
                $mysqlProxyClass->getMethod($mysqlMethod)->getDeclaringClass()->name,
                'MysqlProxy class must have the same public methods as the Mysql. Method - ' . $mysqlMethod . ' missed.'
            );
        }
    }

    /**
     * Tests that predefined list of methods returns proxy instance that is required in chained calls.
     *
     * @param string $method
     * @param array $params
     *
     * @return void
     * @dataProvider returnTypeSelfDataProvider
     */
    public function testReturnTypeSelf(string $method, array $params): void
    {
        $mysqlProxyClass = new \ReflectionClass(MysqlProxy::class);
        $mysqlProxyClass->getProperty('slaveConnection')->setAccessible(true);
        $mysqlProxyClass->getProperty('masterConnection')->setAccessible(true);
        $proxy = $mysqlProxyClass->newInstance(
            $this->getMockForAbstractClass(LoggerInterface::class),
            $this->selectFactoryMock,
            [],
            $this->createMock(MysqlFactory::class)
        );
        $fieldSetter = function (MysqlProxy $proxy, $propertyName, $propertyValue) {
            $proxy->$propertyName = $propertyValue;
        };
        $masterConnecitonMock = $this->createMock(Mysql::class);
        $slaveConnecitonMock = $this->createMock(Mysql::class);
        $fieldSetter = $fieldSetter->bindTo(null, $proxy);
        $fieldSetter($proxy, 'masterConnection', $masterConnecitonMock);
        $fieldSetter($proxy, 'slaveConnection', $slaveConnecitonMock);
        $this->assertInstanceOf(
            MysqlProxy::class,
            call_user_func_array([$proxy, $method], $params),
            'Method ' . $method . ' must return MysqlProxy object reference'
        );
    }

    /**
     * Data provider for testReturnTypeSelf.
     *
     * @return array
     */
    public function returnTypeSelfDataProvider(): array
    {
        return [
            'setUseMasterConnection' => ['setUseMasterConnection', []],
            'beginTransaction' => ['beginTransaction', []],
            'commit' => ['commit', []],
            'rollBack' => ['rollBack', []],
            'dropForeignKey' => ['dropForeignKey', [null, null, null]],
            'purgeOrphanRecords' => ['purgeOrphanRecords', [null, null, null, null]],
            'modifyColumn' => ['modifyColumn', [null, null, null]],
            'modifyTables' => ['modifyTables', [null]],
            'saveDdlCache' => ['saveDdlCache', [null, null, null]],
            'resetDdlCache' => ['resetDdlCache', []],
            'disallowDdlCache' => ['disallowDdlCache', []],
            'allowDdlCache' => ['allowDdlCache', []],
            'modifyColumnByDdl' => ['modifyColumnByDdl', [null, null, null]],
            'setCacheAdapter' => ['setCacheAdapter', [
                $this->getMockForAbstractClass(FrontendInterface::class)
            ]],
            'truncateTable' => ['truncateTable', [null]],
            'startSetup' => ['startSetup', []],
            'endSetup' => ['endSetup', []],
            'disableTableKeys' => ['disableTableKeys', [null]],
            'enableTableKeys' => ['enableTableKeys', [null]],
            'orderRand' => ['orderRand', [
                $this->createMock(Select::class)
            ]],
        ];
    }

    /**
     * @return void
     */
    public function testSelectConnection(): void
    {
        $this->markTestSkipped('Skipped until direct Mysql connection created.');
        $config = [];
        $config['slave'] = [MysqlProxy::CONFIG_MAX_ALLOWED_LAG => 10];
        /** @var MysqlProxy $proxy */
        $proxy = $this->objectManager->getObject(
            MysqlProxy::class,
            [
                'config' => $config['slave']
            ]
        );
        $proxy->getConnection();
    }
}
