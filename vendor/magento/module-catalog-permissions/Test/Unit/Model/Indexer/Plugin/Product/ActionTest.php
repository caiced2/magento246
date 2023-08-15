<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin\Product;

use Magento\Catalog\Model\Product\Action;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    /**
     * @var MockObject|IndexerInterface
     */
    protected $indexerMock;

    /**
     * @var MockObject|ConfigInterface
     */
    protected $configMock;

    /**
     * @var MockObject|Action
     */
    protected $subjectMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Product\Action
     */
    protected $model;

    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Action::class);

        $this->indexerMock = $this->getMockForAbstractClass(
            IndexerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId', 'getState']
        );

        $this->configMock = $this->getMockForAbstractClass(
            ConfigInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isEnabled']
        );
        $this->configMock->expects($this->any())->method('isEnabled')->willReturn(true);

        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Product\Action(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    public function testAfterUpdateAttributesNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1, 2, 3]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterUpdateAttributes($this->subjectMock, $this->subjectMock, [1, 2, 3], [4, 5, 6], 1)
        );
    }

    public function testAfterUpdateAttributesScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(true);
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterUpdateAttributes($this->subjectMock, $this->subjectMock, [1, 2, 3], [4, 5, 6], 1)
        );
    }

    public function testAfterUpdateWebsitesNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1, 2, 3]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->model->afterUpdateWebsites($this->subjectMock, null, [1, 2, 3], [4, 5, 6], 'type');
    }

    public function testAfterUpdateWebsitesScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(true);
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->model->afterUpdateWebsites($this->subjectMock, null, [1, 2, 3], [4, 5, 6], 'type');
    }
}
