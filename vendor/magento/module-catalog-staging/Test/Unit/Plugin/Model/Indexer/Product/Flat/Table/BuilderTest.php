<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Plugin\Model\Indexer\Product\Flat\Table;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Indexer\Product\Flat\Table\BuilderInterface;
use Magento\CatalogStaging\Plugin\Model\Indexer\Product\Flat\Table\Builder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadataMock;

    /**
     * @var BuilderInterface|MockObject
     */
    private $builderMock;

    /**
     * @var Table|MockObject
     */
    private $tableMock;

    /**
     * @var Builder
     */
    private $plugin;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->builderMock = $this->getMockBuilder(BuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->getMockForAbstractClass();
        $this->tableMock = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = $objectManagerHelper->getObject(
            Builder::class,
            [
                'metadataPool' => $this->metadataPoolMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    public function testAfterGetTable()
    {
        $linkField = 'row_id';
        $connectionName = 'not-default';
        $tableName = 'tmp_catalog_product_indexer';
        $indexName = 'ix_catalog_product_row_id';
        $this->tableMock->expects($this->once())
            ->method('getName')
            ->willReturn($tableName);
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->metadataMock);
        $this->metadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $this->metadataMock->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($connectionName);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnectionByName')
            ->with($connectionName)
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())
            ->method('getIndexName')
            ->with($tableName, [$linkField], '')
            ->willReturn($indexName);
        $this->tableMock->expects($this->once())->method('addColumn')
            ->with($linkField, Table::TYPE_INTEGER)
            ->willReturnSelf();
        $this->tableMock->expects($this->once())->method('addIndex')
            ->with($indexName, [$linkField], [])
            ->willReturnSelf();
        $this->assertEquals($this->tableMock, $this->plugin->afterGetTable($this->builderMock, $this->tableMock));
    }
}
