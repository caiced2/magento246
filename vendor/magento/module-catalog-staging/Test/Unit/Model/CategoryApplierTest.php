<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\CatalogStaging\Model\CategoryApplier;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryApplierTest extends TestCase
{
    /**
     * @var State|MockObject
     */
    private $flatState;

    /**
     * @var IndexerRegistry|MockObject
     */
    private $indexerRegistry;

    /**
     * @var CacheContext|MockObject
     */
    private $cacheContext;

    /**
     * @var CategoryApplier
     */
    private $model;

    protected function setUp(): void
    {
        $this->flatState = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerRegistry = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheContext = $this->getMockBuilder(CacheContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CategoryApplier(
            $this->flatState,
            $this->indexerRegistry,
            $this->cacheContext
        );
    }

    public function testExecuteWithFlatEnabled()
    {
        $entityIds = [1];

        $this->flatState->expects($this->once())
            ->method('isFlatEnabled')
            ->willReturn(true);

        $indexerMock = $this->getMockBuilder(IndexerInterface::class)
            ->getMockForAbstractClass();
        $indexerMock->expects($this->once())
            ->method('reindexList')
            ->with($entityIds)
            ->willReturnSelf();

        $this->indexerRegistry->expects($this->once())
            ->method('get')
            ->with(State::INDEXER_ID)
            ->willReturn($indexerMock);

        $this->cacheContext->expects($this->once())
            ->method('registerEntities')
            ->with(Category::CACHE_TAG, $entityIds)
            ->willReturnSelf();

        $this->model->execute($entityIds);
    }

    public function testExecuteWithFlatDisabled()
    {
        $entityIds = [1];

        $this->flatState->expects($this->once())
            ->method('isFlatEnabled')
            ->willReturn(false);

        $this->cacheContext->expects($this->once())
            ->method('registerEntities')
            ->with(Category::CACHE_TAG, $entityIds)
            ->willReturnSelf();

        $this->model->execute($entityIds);
    }

    public function testExecuteWithNoEntities()
    {
        $result = $this->model->execute([]);
        $this->assertNull($result);
    }
}
