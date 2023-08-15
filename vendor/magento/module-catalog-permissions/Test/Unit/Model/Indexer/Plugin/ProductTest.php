<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\Catalog\Model\Product;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
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
     * @var MockObject|\Magento\Catalog\Model\Product
     */
    protected $subjectMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var Product
     */
    protected $model;

    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Product::class);

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

        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Product(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    public function testAfterSaveNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->subjectMock->expects($this->once())->method('getId')->willReturn(1);

        $this->assertEquals($this->subjectMock, $this->model->afterSave($this->subjectMock));
    }

    public function testAfterSaveScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(true);
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->subjectMock->expects($this->once())->method('getId')->willReturn(1);

        $this->assertEquals($this->subjectMock, $this->model->afterSave($this->subjectMock));
    }

    public function testAfterDeleteNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->subjectMock->expects($this->once())->method('getId')->willReturn(1);

        $this->assertEquals($this->subjectMock, $this->model->afterDelete($this->subjectMock));
    }

    public function testAfterDeleteScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(true);
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->subjectMock->expects($this->once())->method('getId')->willReturn(1);

        $this->assertEquals($this->subjectMock, $this->model->afterDelete($this->subjectMock));
    }
}
