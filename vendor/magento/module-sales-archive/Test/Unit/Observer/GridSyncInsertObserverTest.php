<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\GridInterface;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\Archive;
use Magento\SalesArchive\Model\ArchiveFactory;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Observer\GridSyncInsertObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridSyncInsertObserverTest extends TestCase
{
    /**
     * @var GridSyncInsertObserver
     */
    private $observer;

    /**
     * @var MockObject
     */
    private $entityGridMock;

    /**
     * @var MockObject
     */
    private $globalConfigMock;

    /**
     * @var MockObject
     */
    private $configMock;

    /**
     * @var MockObject
     */
    private $archivalListMock;

    /**
     * @var MockObject
     */
    private $archiveFactoryMock;

    /**
     * @var MockObject
     */
    private $observerMock;

    /**
     * @var MockObject
     */
    private $objectMock;

    /**
     * @var MockObject
     */
    private $resourceMock;

    /**
     * @var MockObject
     */
    private $archiveMock;

    protected function setUp(): void
    {
        $this->entityGridMock = $this->getMockForAbstractClass(GridInterface::class);
        $this->globalConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->objectMock = $this->createMock(Order::class);
        $this->archiveFactoryMock = $this->createPartialMock(
            ArchiveFactory::class,
            ['create']
        );
        $this->archivalListMock = $this->createMock(ArchivalList::class);
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->archiveMock = $this->createMock(Archive::class);
        $this->resourceMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->observer = new GridSyncInsertObserver(
            $this->entityGridMock,
            $this->globalConfigMock,
            $this->configMock,
            $this->archiveFactoryMock,
            $this->archivalListMock
        );
    }

    public function testExecuteIfArchiveDisabled()
    {
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(false);
        $this->observerMock->expects($this->never())->method('getObject');
        $this->observer->execute($this->observerMock);
    }

    public function testExecuteIfArchiveEntityNotExist()
    {
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(true);
        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->objectMock);
        $this->archiveFactoryMock->expects($this->once())->method('create')->willReturn($this->archiveMock);
        $this->archivalListMock
            ->expects($this->once())
            ->method('getEntityByObject')
            ->with($this->resourceMock)
            ->willReturn(false);
        $this->objectMock->expects($this->once())->method('getResource')->willReturn($this->resourceMock);

        $this->observer->execute($this->observerMock);
    }

    public function testExecuteIfArchiveEntityExists()
    {
        $invoiceId = 1;
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(true);
        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->objectMock);
        $this->archiveFactoryMock->expects($this->once())->method('create')->willReturn($this->archiveMock);
        $this->archivalListMock
            ->expects($this->once())
            ->method('getEntityByObject')
            ->with($this->resourceMock)
            ->willReturn('invoice');
        $this->objectMock->expects($this->once())->method('getResource')->willReturn($this->resourceMock);
        $this->objectMock->expects($this->once())->method('getId')->willReturn($invoiceId);
        $this->archiveMock
            ->expects($this->once())
            ->method('getIdsInArchive')
            ->with('invoice', [$invoiceId])
            ->willReturn([]);
        $this->archiveMock
            ->expects($this->once())
            ->method('getRelatedIds')
            ->with('invoice', [$invoiceId])
            ->willReturn([$invoiceId]);
        $this->globalConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('dev/grid/async_indexing')
            ->willReturn(false);
        $this->entityGridMock->expects($this->once())->method('refresh')->with($invoiceId);
        $this->observer->execute($this->observerMock);
    }
}
