<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCatalog\Test\Unit\Model\ResourceModel\Product\Indexer\Eav;

use Magento\AdvancedCatalog\Model\ResourceModel\Product\Indexer\Eav\Source;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Helper;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceTest extends TestCase
{
    /**
     * @var Source
     */
    protected $_source;

    /**
     * @var MockObject
     */
    protected $selectMock;

    /**
     * @var Mysql|MockObject
     */
    protected $connectionMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var MetadataPool|MockObject
     */
    protected $metadataPool;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManagerMock;

    /**
     * @var Helper|MockObject
     */
    protected $helperMock;

    /**
     * @var AbstractAttribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var AbstractBackend|MockObject
     */
    protected $backendAttributeMock;

    /**
     * @var StrategyInterface|MockObject
     */
    protected $tableStrategyMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->selectMock = $this->createMock(Select::class);
        $this->selectMock->expects($this->any())->method('from')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('join')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('joinLeft')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('group')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('columns')->willReturnSelf();

        $this->connectionMock = $this->createMock(Mysql::class);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->connectionMock->expects($this->any())->method('describeTable')->willReturn(['column1', 'column2']);

        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->resourceMock->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->attributeMock = $this->createMock(AbstractAttribute::class);
        $this->backendAttributeMock = $this->createMock(
            AbstractBackend::class
        );
        $this->attributeMock->expects($this->any())->method('getBackend')
            ->willReturn($this->backendAttributeMock);

        $this->eavConfigMock = $this->createMock(Config::class);
        $this->eavConfigMock->expects($this->any())->method('getAttribute')->willReturn(
            $this->attributeMock
        );

        $metadata = $this->createMock(EntityMetadata::class);

        $this->metadataPool = $this->createMock(MetadataPool::class);

        $this->metadataPool->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($metadata);

        $this->eventManagerMock = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['dispatch']
        );

        $this->helperMock = $this->createMock(Helper::class);

        $connectionName = 'index';
        $this->tableStrategyMock = $this->getMockForAbstractClass(StrategyInterface::class);

        $this->tableStrategyMock->expects($this->any())->method('getTableName')->willReturnArgument(0);
        $this->_source = new Source(
            $this->contextMock,
            $this->tableStrategyMock,
            $this->eavConfigMock,
            $this->eventManagerMock,
            $this->helperMock,
            $connectionName
        );
        $reflection = new \ReflectionClass(get_class($this->_source));
        $reflectionProperty = $reflection->getProperty('metadataPool');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->_source, $this->metadataPool);
    }

    /**
     * Test prepare relation index with using idx table
     *
     * @return void
     */
    public function testPrepareRelationIndexUseIdxTable()
    {
        $this->tableStrategyMock->expects($this->any())->method('getUseIdxTable')->willReturn(true);
        $this->eventManagerMock->expects($this->once())->method('dispatch')->with(
            'prepare_catalog_product_index_select'
        );
        $this->connectionMock->expects($this->never())->method('createTemporaryTableLike');
        $this->connectionMock->expects($this->never())->method('dropTemporaryTable');
        $this->assertInstanceOf(
            Source::class,
            $this->_source->reindexAll()
        );
    }

    /**
     * Test prepare relation index without using idx table
     *
     * @return void
     */
    public function testPrepareRelationIndexNotUseIdxTable()
    {
        $this->tableStrategyMock->expects($this->any())->method('getUseIdxTable')->willReturn(false);
        $this->eventManagerMock->expects($this->once())->method('dispatch')->with(
            'prepare_catalog_product_index_select'
        );
        $this->connectionMock->expects($this->atLeastOnce())->method('createTemporaryTableLike');
        $this->connectionMock->expects($this->once())->method('dropTemporaryTable');
        $this->assertInstanceOf(
            Source::class,
            $this->_source->reindexEntities([1])
        );
    }
}
