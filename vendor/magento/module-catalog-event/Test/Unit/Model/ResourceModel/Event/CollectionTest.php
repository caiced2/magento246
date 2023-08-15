<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test class for \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
 */
namespace Magento\CatalogEvent\Test\Unit\Model\ResourceModel\Event;

use Magento\CatalogEvent\Model\ResourceModel\Event\Collection;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CollectionTest extends TestCase
{
    /**
     * Main table name
     */
    const MAIN_TABLE = 'main_table';

    /**#@+
     * Predefined store ids
     */
    const STORE_ID = 0;

    const CURRENT_STORE_ID = 1;

    /**#@-*/

    /**
     * Predefined getCheckSql result
     */
    const GET_CHECK_SQL_RESULT = 'sql_result';

    /**
     * Expected values for leftJoin method
     *
     * @var array
     */
    protected $_joinValues = [
        1 => [
            'name' => ['event_image' => self::MAIN_TABLE],
            'condition' => 'event_image.event_id = main_table.event_id AND event_image.store_id = %CURRENT_STORE_ID%',
            'columns' => ['image' => self::GET_CHECK_SQL_RESULT],
        ],
        2 => [
            'name' => ['event_image_default' => self::MAIN_TABLE],
            'condition' => 'event_image_default.event_id = main_table.event_id '
                . 'AND event_image_default.store_id = %STORE_ID%',
            'columns' => [],
        ],
    ];

    /**
     * Replace values for store ids
     *
     * @var array
     */
    protected $_joinReplaces = ['%CURRENT_STORE_ID%' => self::CURRENT_STORE_ID, '%STORE_ID%' => self::STORE_ID];

    /**
     * Expected values for getCheckSql method
     *
     * @var array
     */
    protected $_checkSqlValues = [
        'condition' => 'event_image.image IS NULL',
        'true' => 'event_image_default.image',
        'false' => 'event_image.image',
    ];

    /**
     * @var Collection
     */
    protected $_collection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        foreach (array_keys($this->_joinValues) as $key) {
            $this->_joinValues[$key]['condition'] = str_replace(
                array_keys($this->_joinReplaces),
                array_values($this->_joinReplaces),
                $this->_joinValues[$key]['condition']
            );
        }

        $eventManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $store = $this->createPartialMock(Store::class, ['getId', '__sleep']);
        $store->expects($this->once())->method('getId')->willReturn(self::CURRENT_STORE_ID);

        $storeManager = $this->createPartialMock(StoreManager::class, ['getStore']);
        $storeManager->expects($this->once())->method('getStore')->willReturn($store);

        $select = $this->createPartialMock(Select::class, ['joinLeft', 'from', 'columns']);
        $withArgs = $willReturnArgs = [];

        foreach ($this->_joinValues as $arguments) {
            $withArgs[] = [$arguments['name'], $arguments['condition'], $arguments['columns']];
            $willReturnArgs[] = $select;
        }
        $select
            ->method('joinLeft')
            ->withConsecutive(...$withArgs)
            ->willReturnOnConsecutiveCalls(...$willReturnArgs);

        $connection = $this->createPartialMock(
            Mysql::class,
            ['select', 'quoteInto', 'getCheckSql', 'quote']
        );
        $connection->expects($this->once())->method('select')->willReturn($select);
        $connection->expects($this->exactly(1))->method('quoteInto')->willReturnCallback(
            function ($text, $value) {
                $value = is_array($value) ? $value : (string) $value;
                return str_replace('?', $value, $text);
            }
        );
        $connection->expects($this->exactly(1))
            ->method('getCheckSql')
            ->willReturnCallback([$this, 'verifyGetCheckSql']);

        $resource = $this->getMockForAbstractClass(
            AbstractDb::class,
            [],
            '',
            false,
            true,
            true,
            ['getConnection', 'getMainTable', 'getTable']
        );
        $resource->expects($this->once())->method('getConnection')->willReturn($connection);
        $resource->expects($this->once())->method('getMainTable')->willReturn(self::MAIN_TABLE);
        $resource->expects($this->exactly(3))
            ->method('getTable')
            ->willReturn(self::MAIN_TABLE);

        $fetchStrategy = $this->getMockForAbstractClass(
            FetchStrategyInterface::class
        );
        $entityFactory = $this->createMock(EntityFactory::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $localeDate = $this->createMock(Timezone::class);
        $eavConfig = $this->createMock(Config::class);
        $metadataPool = $this->createMock(MetadataPool::class);

        $this->_collection = new Collection(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $localeDate,
            $eavConfig,
            $metadataPool,
            null,
            $resource
        );
    }

    protected function tearDown(): void
    {
        $this->_collection = null;
    }

    /**
     * Callback and verify getCheckSql method arguments.
     *
     * @param string $condition     expression
     * @param string $true          true value
     * @param string $false         false value
     *
     * @return string
     */
    public function verifyGetCheckSql($condition, $true, $false): string
    {
        $this->assertEquals($this->_checkSqlValues['condition'], $condition);
        $this->assertEquals($this->_checkSqlValues['true'], $true);
        $this->assertEquals($this->_checkSqlValues['false'], $false);

        return self::GET_CHECK_SQL_RESULT;
    }

    public function testAddImageData(): void
    {
        $this->assertInstanceOf(
            Collection::class,
            $this->_collection->addImageData()
        );
    }
}
