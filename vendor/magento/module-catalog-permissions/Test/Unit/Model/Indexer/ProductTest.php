<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer;

use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\Model\Indexer\Category\Action\Full;
use Magento\CatalogPermissions\Model\Indexer\Category\Action\FullFactory;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\CatalogPermissions\Model\Indexer\Product\Action\Rows;
use Magento\CatalogPermissions\Model\Indexer\Product\Action\RowsFactory;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTest extends TestCase
{
    /**
     * @var Product
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $fullMock;

    /**
     * @var MockObject
     */
    protected $rowsMock;

    /**
     * @var IndexerInterface|MockObject
     */
    protected $indexerMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var CacheContext|MockObject
     */
    protected $cacheContextMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fullMock = $this->createPartialMock(
            FullFactory::class,
            ['create']
        );

        $this->rowsMock = $this->createPartialMock(
            RowsFactory::class,
            ['create']
        );

        $methods = ['getId', 'load', 'isInvalid', 'isWorking'];
        $this->indexerMock = $this->getMockForAbstractClass(
            IndexerInterface::class,
            [],
            '',
            false,
            false,
            true,
            $methods
        );

        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $this->model = new Product(
            $this->fullMock,
            $this->rowsMock,
            $this->indexerRegistryMock
        );

        $this->cacheContextMock = $this->createMock(CacheContext::class);

        $cacheContextProperty = new \ReflectionProperty(
            Product::class,
            'cacheContext'
        );
        $cacheContextProperty->setAccessible(true);
        $cacheContextProperty->setValue($this->model, $this->cacheContextMock);
    }

    /**
     * @return void
     */
    public function testExecuteWithIndexerWorking(): void
    {
        $ids = [1, 2, 3];

        $this->indexerMock->expects($this->once())->method('isWorking')->willReturn(true);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $rowMock = $this->createPartialMock(
            Rows::class,
            ['execute']
        );
        $rowMock
            ->method('execute')
            ->withConsecutive([$ids, true], [$ids, false])
            ->willReturnOnConsecutiveCalls($rowMock, $rowMock);

        $this->rowsMock->expects($this->once())->method('create')->willReturn($rowMock);

        $this->cacheContextMock->expects($this->once())
            ->method('registerEntities')
            ->with(\Magento\Catalog\Model\Product::CACHE_TAG, $ids);

        $this->model->execute($ids);
    }

    /**
     * @return void
     */
    public function testExecuteWithIndexerNotWorking(): void
    {
        $ids = [1, 2, 3];

        $this->indexerMock->expects($this->once())->method('isWorking')->willReturn(false);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $rowMock = $this->createPartialMock(
            Rows::class,
            ['execute']
        );
        $rowMock->expects($this->once())->method('execute')->with($ids, false)->willReturnSelf();

        $this->rowsMock->expects($this->once())->method('create')->willReturn($rowMock);

        $this->model->execute($ids);
    }

    /**
     * @return void
     */
    public function testExecuteFull(): void
    {
        /** @var Full $categoryIndexerFlatFull */
        $categoryIndexerFlatFull = $this->createMock(
            Full::class
        );
        $this->fullMock->expects($this->once())
            ->method('create')
            ->willReturn($categoryIndexerFlatFull);
        $categoryIndexerFlatFull->expects($this->once())
            ->method('execute');
        $this->cacheContextMock->expects($this->once())
            ->method('registerTags')
            ->with(
                [
                    Category::CACHE_TAG,
                    \Magento\Catalog\Model\Product::CACHE_TAG
                ]
            );
        $this->model->executeFull();
    }
}
