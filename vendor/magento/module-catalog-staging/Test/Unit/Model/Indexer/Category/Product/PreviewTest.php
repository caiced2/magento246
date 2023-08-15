<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Indexer\Category\Product;

use Magento\CatalogStaging\Model\Indexer\Category\Product\Preview;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\IndexScopeResolverInterface as TableResolver;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class PreviewTest extends TestCase
{

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerMock;

    /**
     * @var TableResolver
     */
    private $tableResolverMock;

    /**
     * @var Preview
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')
            ->willReturnSelf();
        $connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $connectionMock->expects($this->any())->method('select')->willReturn($selectMock);
        $resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $resourceMock->expects($this->any())->method('getTableName')->willReturnArgument(0);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $configMock =$this->createMock(\Magento\Catalog\Model\Config::class);
        $queryGeneratorMock = $this->createMock(\Magento\Framework\DB\Query\Generator::class);
        $metadataPoolMock = $this->createMock(\Magento\Framework\EntityManager\MetadataPool::class);
        $tableMaintainerMock =
            $this->createMock(\Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class);
        $this->tableResolverMock =
            $this->createMock(\Magento\Framework\Search\Request\IndexScopeResolverInterface::class);
        $this->model = new Preview(
            $resourceMock,
            $this->storeManagerMock,
            $configMock,
            $queryGeneratorMock,
            $metadataPoolMock,
            $tableMaintainerMock,
            $this->tableResolverMock
        );
    }

    /**
     * Tests that store is used during reindex
     * @return void
     */
    public function testReindexUsesStoreId(): void
    {
        $storeId = 1;
        $storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getRootCategoryId'])
            ->getMockForAbstractClass();
        $storeMock->expects($this->once())->method('getRootCategoryId')->willReturn(false);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);
        $this->model->executeScoped(1, $storeId);
    }
}
