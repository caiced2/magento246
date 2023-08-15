<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Event\EventStatusUpdater;
use Magento\AdobeCommerceEventsClient\Model\Event as EventModel;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event as EventResourceModel;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventStatusUpdater class
 */
class EventStatusUpdaterTest extends TestCase
{
    /**
     * @var EventStatusUpdater
     */
    private EventStatusUpdater $statusUpdater;

    /**
     * @var EventRepositoryInterface|MockObject
     */
    private $eventRepositoryMock;

    /**
     * @var EventResourceModel|MockObject
     */
    private $eventResourceModelMock;

    protected function setUp(): void
    {
        $this->eventRepositoryMock = $this->getMockForAbstractClass(EventRepositoryInterface::class);
        $this->eventResourceModelMock = $this->createMock(EventResourceModel::class);

        $this->statusUpdater = new EventStatusUpdater(
            $this->eventRepositoryMock,
            $this->eventResourceModelMock
        );
    }

    /**
     * Checks the updating of events with a success status.
     *
     * @return void
     */
    public function testUpdateStatus()
    {
        $connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $connectionMock->expects(self::once())
            ->method('update')
            ->with(
                'event_data',
                [
                    'status' => EventInterface::SUCCESS_STATUS,
                    'info' => 'test'
                ],
                ['event_id in (?)' => [1,2]]
            );
        $this->eventResourceModelMock->expects(self::once())
            ->method('getMainTable')
            ->willReturn('event_data');
        $this->eventResourceModelMock->expects(self::once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $this->statusUpdater->updateStatus([1, 2], EventInterface::SUCCESS_STATUS, 'test');
    }

    /**
     * Checks the updating of events after a failure to send event data.
     *
     * @return void
     */
    public function testUpdateFailure()
    {
        $maxRetries = 5;
        $eventModelOne = $this->createMock(EventModel::class);
        $eventModelOne->expects(self::once())
            ->method('getRetriesCount')
            ->willReturn($maxRetries);
        $eventModelOne->expects(self::once())
            ->method('setStatus')
            ->with(EventInterface::FAILURE_STATUS);
        $eventModelOne->expects(self::never())
            ->method('setRetriesCount');

        $eventModelTwo = $this->createMock(EventModel::class);
        $eventModelTwo->expects(self::once())
            ->method('getRetriesCount')
            ->willReturn(0);
        $eventModelTwo->expects(self::once())
            ->method('setRetriesCount')
            ->with(1);
        $eventModelTwo->expects(self::once())
            ->method('setStatus')
            ->with(EventInterface::WAITING_STATUS);

        $this->eventRepositoryMock->expects(self::exactly(2))
            ->method('getById')
            ->willReturnOnConsecutiveCalls($eventModelOne, $eventModelTwo);

        $this->eventRepositoryMock->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive([$eventModelOne], [$eventModelTwo]);
        $this->statusUpdater->updateFailure([1, 2], $maxRetries);
    }
}
