<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model;

use Magento\Framework\Event\Manager;
use Magento\Framework\Event\ManagerInterface;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\Archive;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchiveTest extends TestCase
{
    /**
     * @var Archive
     */
    protected $archive;

    /**
     * @var \Magento\SalesArchive\Model\ResourceModel\Archive|MockObject
     */
    protected $resourceArchive;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManager;

    /**
     * @var String
     */
    protected $archiveClassName = Archive::class;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resourceArchive = $this->createMock(\Magento\SalesArchive\Model\ResourceModel\Archive::class);
        $this->eventManager = $this->createMock(Manager::class);

        $this->archive = new Archive($this->resourceArchive, $this->eventManager);
    }

    /**
     * @return void
     */
    public function testUpdateGridRecords(): void
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $this->resourceArchive->expects($this->once())
            ->method('updateGridRecords')
            ->with($this->archive, $archiveEntity, $ids);
        $result = $this->archive->updateGridRecords($archiveEntity, $ids);
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    /**
     * @return void
     */
    public function testGetIdsInArchive(): void
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $relatedIds = [001, 003, 004];
        $this->resourceArchive->expects($this->once())
            ->method('getIdsInArchive')
            ->with($archiveEntity, $ids)
            ->willReturn($relatedIds);
        $result = $this->archive->getIdsInArchive($archiveEntity, $ids);
        $this->assertEquals($relatedIds, $result);
    }

    /**
     * @return void
     */
    public function testGetRelatedIds(): void
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $relatedIds = [001, 003, 004];
        $this->resourceArchive->expects($this->once())
            ->method('getRelatedIds')
            ->with($archiveEntity, $ids)
            ->willReturn($relatedIds);
        $result = $this->archive->getRelatedIds($archiveEntity, $ids);
        $this->assertEquals($relatedIds, $result);
    }

    /**
     * @return void
     */
    public function testArchiveOrders(): void
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchiveExpression')
            ->willReturn($ids);

        $this->resourceArchive
            ->method('moveToArchive')
            ->withConsecutive(
                [ArchivalList::ORDER, $entity, $ids],
                [ArchivalList::INVOICE, $order, $ids],
                [ArchivalList::SHIPMENT, $order, $ids],
                [ArchivalList::CREDITMEMO, $order, $ids]
            )
            ->willReturnOnConsecutiveCalls(
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive
            );

        $this->resourceArchive
            ->method('commit')
            ->willReturn($this->resourceArchive);
        $this->resourceArchive
            ->method('removeFromGrid')
            ->withConsecutive(
                [ArchivalList::ORDER, $entity, $ids],
                [ArchivalList::INVOICE, $order, $ids],
                [ArchivalList::SHIPMENT, $order, $ids],
                [ArchivalList::CREDITMEMO, $order, $ids]
            )
            ->willReturnOnConsecutiveCalls(
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive
            );

        $event = 'magento_salesarchive_archive_archive_orders';
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with($event, ['order_ids' => $ids]);

        $result = $this->archive->archiveOrders();
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    /**
     * @return void
     */
    public function testArchiveOrdersException(): void
    {
        $this->expectException('Exception');
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchiveExpression')
            ->willReturn($ids);
        $this->resourceArchive
            ->method('moveToArchive')
            ->withConsecutive([ArchivalList::ORDER, $entity, $ids])
            ->willReturnOnConsecutiveCalls($this->throwException(new \Exception()));
        $this->resourceArchive
            ->method('rollback')
            ->willReturn($this->resourceArchive);
        $result = $this->archive->archiveOrders();
        $this->assertInstanceOf('Exception', $result);
    }

    /**
     * @return void
     */
    public function testArchiveOrdersById(): void
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchive')
            ->with($ids, false)
            ->willReturn($ids);

        $this->resourceArchive
            ->method('moveToArchive')
            ->withConsecutive(
                [ArchivalList::ORDER, $entity, $ids],
                [ArchivalList::INVOICE, $order, $ids],
                [ArchivalList::SHIPMENT, $order, $ids],
                [ArchivalList::CREDITMEMO, $order, $ids]
            )
            ->willReturnOnConsecutiveCalls(
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive
            );
        $this->resourceArchive
            ->method('commit')
            ->willReturn($this->resourceArchive);
        $this->resourceArchive
            ->method('removeFromGrid')
            ->withConsecutive(
                [ArchivalList::ORDER, $entity, $ids],
                [ArchivalList::INVOICE, $order, $ids],
                [ArchivalList::SHIPMENT, $order, $ids],
                [ArchivalList::CREDITMEMO, $order, $ids]
            )
            ->willReturnOnConsecutiveCalls(
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive
            );

        $event = 'magento_salesarchive_archive_archive_orders';
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with($event, ['order_ids' => $ids]);

        $result = $this->archive->archiveOrdersById($ids);
        $this->assertEquals($ids, $result);
    }

    /**
     * @return void
     */
    public function testArchiveOrdersByIdException(): void
    {
        $this->expectException('Exception');
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchive')
            ->with($ids, false)
            ->willReturn($ids);
        $this->resourceArchive
            ->method('beginTransaction')
            ->willReturnSelf();
        $this->resourceArchive
            ->method('moveToArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)
            ->willThrowException(new \Exception());
        $this->resourceArchive
            ->method('rollback')
            ->willReturnSelf();
        $result = $this->archive->archiveOrdersById($ids);
        $this->assertInstanceOf('Exception', $result);
    }

    /**
     * @return void
     */
    public function testRemoveOrdersFromArchive(): void
    {
        $this->resourceArchive->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchive
            ->method('removeFromArchive')
            ->withConsecutive(
                [ArchivalList::ORDER],
                [ArchivalList::INVOICE],
                [ArchivalList::SHIPMENT],
                [ArchivalList::CREDITMEMO]
            )
            ->willReturnOnConsecutiveCalls(
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive,
                $this->resourceArchive
            );
        $this->resourceArchive
            ->method('commit')
            ->willReturn($this->resourceArchive);

        $result = $this->archive->removeOrdersFromArchive();
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    /**
     * @return void
     */
    public function testRemoveOrdersFromArchiveException(): void
    {
        $this->expectException('Exception');
        $this->resourceArchive->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchive
            ->method('removeFromArchive')
            ->with(ArchivalList::ORDER)
            ->willThrowException(new \Exception());
        $this->resourceArchive
            ->method('rollback')
            ->willThrowException(new \Exception());
        $result = $this->archive->removeOrdersFromArchive();
        $this->assertInstanceOf('Exception', $result);
    }

    /**
     * @return void
     */
    public function testRemoveOrdersFromArchiveById(): void
    {
        $ids = [100021, 100023, 100054];
        $this->resourceArchive->expects($this->once())
            ->method('removeOrdersFromArchiveById')
            ->with($ids)
            ->willReturn($ids);

        $result = $this->archive->removeOrdersFromArchiveById($ids);
        $this->assertEquals($ids, $result);
    }
}
