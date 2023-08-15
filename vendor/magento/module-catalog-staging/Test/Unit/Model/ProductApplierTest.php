<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogStaging\Helper\ReindexPool;
use Magento\CatalogStaging\Model\ProductApplier;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductApplierTest extends TestCase
{
    /**
     * @var Collection|MockObject
     */
    private $productCollection;

    /**
     * @var ReindexPool|MockObject
     */
    private $reindexPool;

    /**
     * @var IndexerRegistry|MockObject
     */
    private $indexerRegistry;

    /**
     * @var CacheContext|MockObject
     */
    private $cacheContext;

    /**
     * @var ProductApplier
     */
    private $model;

    protected function setUp(): void
    {
        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reindexPool = $this->getMockBuilder(ReindexPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerRegistry = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheContext = $this->getMockBuilder(CacheContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new ProductApplier(
            $this->productCollection,
            $this->reindexPool,
            $this->indexerRegistry,
            $this->cacheContext
        );
    }

    public function testExecute()
    {
        $entityIds = [1];
        $affectedCategories = [2];

        $this->reindexPool->expects($this->once())
            ->method('reindexList')
            ->with($entityIds)
            ->willReturnSelf();

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['fetchCol'])
            ->getMockForAbstractClass();

        $selectMock->expects($this->once())
            ->method('reset')
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('distinct')
            ->with(true)
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('from')
            ->with('catalog_category_product', ['category_id'])
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('where')
            ->with('product_id IN (?)', $entityIds)
            ->willReturnSelf();

        $adapterMock->expects($this->once())
            ->method('fetchCol')
            ->with($selectMock)
            ->willReturn($affectedCategories);

        $this->productCollection->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);
        $this->productCollection->expects($this->once())
            ->method('getTable')
            ->with('catalog_category_product')
            ->willReturn('catalog_category_product');
        $this->productCollection->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);

        $this->cacheContext->expects($this->exactly(2))
            ->method('registerEntities')
            ->willReturnMap([
                [Category::CACHE_TAG, $affectedCategories, $this->cacheContext],
                [Product::CACHE_TAG, $entityIds, $this->cacheContext],
            ]);

        $this->model->execute($entityIds);
    }

    public function testExecuteWithNoEntityIds()
    {
        $result = $this->model->execute([]);
        $this->assertNull($result);
    }
}
