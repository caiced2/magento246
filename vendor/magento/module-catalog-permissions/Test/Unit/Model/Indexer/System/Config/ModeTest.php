<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\System\Config;

use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\CatalogPermissions\Model\Indexer\System\Config\Mode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Indexer\Model\Indexer\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModeTest extends TestCase
{
    /**
     * @var Mode
     */
    protected $model;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configMock;

    /**
     * @var State|MockObject
     */
    protected $indexerStateMock;

    /**
     * @var IndexerInterface|MockObject
     */
    protected $indexerMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->indexerStateMock = $this->createPartialMock(
            State::class,
            ['loadByIndexer', 'setStatus', 'save']
        );
        $this->indexerMock = $this->getMockForAbstractClass(
            IndexerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setScheduled']
        );
        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Mode::class,
            [
                'config' => $this->configMock,
                'indexerRegistry' => $this->indexerRegistryMock,
                'indexerState' => $this->indexerStateMock
            ]
        );
    }

    public function dataProviderProcessValueEqual()
    {
        return [['0', '0'], ['', '0'], ['0', ''], ['1', '1']];
    }

    /**
     * @param string $oldValue
     * @param string $value
     * @dataProvider dataProviderProcessValueEqual
     */
    public function testProcessValueEqual($oldValue, $value)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            null,
            'default'
        )->willReturn(
            $oldValue
        );

        $this->model->setValue($value);

        $this->indexerStateMock->expects($this->never())->method('loadByIndexer');
        $this->indexerStateMock->expects($this->never())->method('setStatus');
        $this->indexerStateMock->expects($this->never())->method('save');

        $this->indexerMock->expects($this->never())->method('setScheduled');

        $this->model->processValue();
    }

    public function dataProviderProcessValueOn()
    {
        return [['0', '1'], ['', '1']];
    }

    /**
     * @param string $oldValue
     * @param string $value
     * @dataProvider dataProviderProcessValueOn
     */
    public function testProcessValueOn($oldValue, $value)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            null,
            'default'
        )->willReturn(
            $oldValue
        );

        $this->model->setValue($value);

        $map = [
            [
                Category::INDEXER_ID,
                Product::INDEXER_ID,
            ],
            [$this->returnSelf(), $this->returnSelf()],
        ];
        $this->indexerStateMock->expects($this->exactly(2))->method('loadByIndexer')->willReturnMap($map);
        $this->indexerStateMock->expects(
            $this->exactly(2)
        )->method(
            'setStatus'
        )->with(
            'invalid'
        )->willReturnSelf();
        $this->indexerStateMock->expects($this->exactly(2))->method('save')->willReturnSelf();

        $this->indexerMock->expects($this->never())->method('setScheduled');

        $this->model->processValue();
    }

    public function dataProviderProcessValueOff()
    {
        return [['1', '0'], ['1', '']];
    }

    /**
     * @param string $oldValue
     * @param string $value
     * @dataProvider dataProviderProcessValueOff
     */
    public function testProcessValueOff($oldValue, $value)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            null,
            'default'
        )->willReturn(
            $oldValue
        );

        $this->model->setValue($value);

        $this->indexerStateMock->expects($this->never())->method('loadByIndexer');
        $this->indexerStateMock->expects($this->never())->method('setStatus');
        $this->indexerStateMock->expects($this->never())->method('save');

        $this->indexerMock->expects($this->exactly(2))->method('setScheduled')->with(false);
        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [Category::INDEXER_ID, $this->indexerMock],
                [Product::INDEXER_ID, $this->indexerMock],
            ]);

        $this->model->processValue();
    }
}
