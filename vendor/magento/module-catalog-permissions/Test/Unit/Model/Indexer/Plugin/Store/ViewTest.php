<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin\Store;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Plugin\Store\View;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer\State;
use Magento\Store\Model\ResourceModel\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
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
     * @var MockObject|Store
     */
    protected $subjectMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var View
     */
    protected $model;

    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Store::class);

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
        $this->model = new View(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    public function testAfterSaveNewObject()
    {
        $this->mockIndexerMethods();
        $storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['isObjectNew', 'dataHasChangedFor']
        );
        $storeMock->expects($this->once())->method('isObjectNew')->willReturn(true);

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $storeMock)
        );
    }

    public function testAfterSaveHasChanged()
    {
        $storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['isObjectNew', 'dataHasChangedFor']
        );

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $storeMock)
        );
    }

    public function testAfterSaveNoNeed()
    {
        $storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['isObjectNew', 'dataHasChangedFor']
        );

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $storeMock)
        );
    }

    /**
     * @return MockObject|State
     */
    protected function getStateMock()
    {
        $stateMock = $this->createPartialMock(
            State::class,
            ['setStatus', 'save']
        );
        $stateMock->expects($this->once())->method('setStatus')->with('invalid')->willReturnSelf();
        $stateMock->expects($this->once())->method('save')->willReturnSelf();

        return $stateMock;
    }

    protected function mockIndexerMethods()
    {
        $this->indexerMock->expects($this->exactly(2))->method('invalidate');
        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(Category::INDEXER_ID)],
                [$this->equalTo(Product::INDEXER_ID)]
            )
            ->willReturn($this->indexerMock);
    }
}
