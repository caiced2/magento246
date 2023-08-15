<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCatalog\Test\Unit\Model\ResourceModel\Product\Indexer\Price;

use Magento\AdvancedCatalog\Model\ResourceModel\Product\Indexer\Price\Grouped;
use Magento\Catalog\Api\Data\ProductInterface;
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
use Magento\Framework\Module\Manager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupedTest extends TestCase
{
    /**
     * @var Grouped
     */
    protected $_grouped;

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
     * @var EntityMetadata|MockObject
     */
    protected $matadataMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManagerMock;

    /**
     * @var Manager|MockObject
     */
    protected $managerMock;

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

    protected function setUp(): void
    {
        $this->selectMock = $this->createMock(Select::class);
        $this->selectMock->expects($this->any())->method('from')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('join')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('columns')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('joinLeft')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('group')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('where')->willReturn($this->selectMock);

        $this->connectionMock = $this->createMock(Mysql::class);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->connectionMock->expects($this->any())->method('describeTable')->willReturn(['column1', 'column2']);
        $this->connectionMock->expects($this->any())->method('fetchOne')->willReturn([1, 2]);

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

        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matadataMock = $this->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->matadataMock);

        $this->eventManagerMock = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['dispatch']
        );

        $this->managerMock = $this->createMock(Manager::class);

        $connectionName = 'index';

        $this->tableStrategyMock = $this->getMockForAbstractClass(StrategyInterface::class);
        $this->tableStrategyMock->expects($this->any())->method('getTableName')->willReturnArgument(0);

        $this->_grouped = new Grouped(
            $this->contextMock,
            $this->tableStrategyMock,
            $this->eavConfigMock,
            $this->eventManagerMock,
            $this->managerMock,
            $connectionName
        );
        $reflection = new \ReflectionClass(get_class($this->_grouped));
        $reflectionProperty = $reflection->getProperty('metadataPool');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->_grouped, $this->metadataPool);
    }

    /**
     * Test prepare grouped product price data with using idx table
     *
     * @return void
     */
    public function testPrepareGroupedProductPriceDataUseIdxTable()
    {
        $this->_grouped->setTypeId(1);
        $this->matadataMock->expects($this->once())->method('getLinkField')->willReturn('entity_id');
        $this->tableStrategyMock->expects($this->any())->method('getUseIdxTable')->willReturn(true);
        $this->eventManagerMock->expects($this->once())->method('dispatch')
            ->with('catalog_product_prepare_index_select');
        $this->connectionMock->expects($this->never())->method('createTemporaryTableLike');
        $this->connectionMock->expects($this->never())->method('dropTemporaryTable');
        $this->assertInstanceOf(
            Grouped::class,
            $this->_grouped->reindexAll()
        );
    }

    /**
     * Test prepare grouped product price data without using idx table
     *
     * @return void
     */
    public function testPrepareGroupedProductPriceDataNotUseIdxTable()
    {
        $this->_grouped->setTypeId(1);
        $this->matadataMock->expects($this->once())->method('getLinkField')->willReturn('entity_id');
        $this->tableStrategyMock->expects($this->any())->method('getUseIdxTable')->willReturn(false);
        $this->eventManagerMock->expects($this->once())->method('dispatch')->with(
            'catalog_product_prepare_index_select'
        );
        $this->connectionMock->expects($this->atLeastOnce())->method('createTemporaryTableLike');
        $this->connectionMock->expects($this->once())->method('dropTemporaryTable');
        $this->assertInstanceOf(
            Grouped::class,
            $this->_grouped->reindexEntity([1])
        );
    }
}
