<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin\Store;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Model\ResourceModel\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
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
     * @var MockObject|Group
     */
    protected $subjectMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Store\Group
     */
    protected $model;

    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Group::class);
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
        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Store\Group(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    /**
     * @param array $valueMap
     * @dataProvider changedDataProvider
     */
    public function testAfterSave($valueMap)
    {
        $this->mockIndexerMethods();
        $groupMock = $this->createPartialMock(
            \Magento\Store\Model\Group::class,
            ['dataHasChangedFor', 'isObjectNew']
        );
        $groupMock->expects($this->exactly(2))->method('dataHasChangedFor')->willReturnMap($valueMap);
        $groupMock->expects($this->once())->method('isObjectNew')->willReturn(false);

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $groupMock)
        );
    }

    /**
     * @param array $valueMap
     * @dataProvider changedDataProvider
     */
    public function testAfterSaveNotNew($valueMap)
    {
        $groupMock = $this->createPartialMock(
            \Magento\Store\Model\Group::class,
            ['dataHasChangedFor', 'isObjectNew']
        );
        $groupMock->expects($this->exactly(2))->method('dataHasChangedFor')->willReturnMap($valueMap);
        $groupMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $groupMock)
        );
    }

    public function changedDataProvider()
    {
        return [
            [
                [['root_category_id', true], ['website_id', false]],
                [['root_category_id', false], ['website_id', true]],
            ]
        ];
    }

    public function testAfterSaveWithoutChanges()
    {
        $groupMock = $this->createPartialMock(
            \Magento\Store\Model\Group::class,
            ['dataHasChangedFor', 'isObjectNew']
        );
        $groupMock->expects(
            $this->exactly(2)
        )->method(
            'dataHasChangedFor'
        )->willReturnMap(
            [['root_category_id', false], ['website_id', false]]
        );
        $groupMock->expects($this->never())->method('isObjectNew');

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $groupMock)
        );
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
