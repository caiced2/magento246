<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Event\Client;
use Magento\AdobeCommerceEventsClient\Event\EventBatchSender;
use Magento\AdobeCommerceEventsClient\Event\EventRetrieverInterface;
use Magento\AdobeCommerceEventsClient\Event\EventStatusUpdater;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Lock\LockManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for EventBatchSender class
 */
class EventBatchSenderTest extends TestCase
{
    /**
     * @var EventBatchSender
     */
    private EventBatchSender $batchSender;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var EventRetrieverInterface|MockObject
     */
    private $eventRetrieverMock;

    /**
     * @var EventStatusUpdater|MockObject
     */
    private $eventStatusUpdaterMock;

    /**
     * @var LockManagerInterface|MockObject
     */
    private $lockManagerMock;

    /**
     * @var array
     */
    private array $events = [
        '1' => ['data' => 'dataOne', 'eventCode' => 'codeOne'],
        '2' => ['data' => 'dataTwo', 'eventCode' => 'codeTwo']
    ];

    /**
     * @var int
     */
    private int $maxRetries = 5;

    protected function setUp(): void
    {
        $this->lockManagerMock = $this->getMockForAbstractClass(LockManagerInterface::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->eventRetrieverMock = $this->getMockForAbstractClass(EventRetrieverInterface::class);
        $this->eventStatusUpdaterMock = $this->createMock(EventStatusUpdater::class);
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->batchSender = new EventBatchSender(
            $this->lockManagerMock,
            $this->scopeConfigMock,
            $this->clientMock,
            $this->eventRetrieverMock,
            $this->eventStatusUpdaterMock,
            $loggerMock
        );
    }

    /**
     * Tests that batch send are not running if the process is locked.
     *
     * @return void
     */
    public function testEventBatchSendingIsLocked()
    {
        $this->lockManagerMock->expects(self::once())
            ->method('lock')
            ->willReturn(false);
        $this->eventRetrieverMock->expects(self::never())
            ->method('getEventsWithLimit');

        $this->batchSender->sendEventDataBatches();
    }

    /**
     * Tests successful sending of a batch of event data.
     *
     * @return void
     */
    public function testSendEventDataBatchSuccess()
    {
        $eventIds = [1, 2];
        $this->lockManagerMock->expects(self::once())
            ->method('lock')
            ->willReturn(true);
        $this->eventRetrieverMock->expects(self::never())
            ->method('getEvents');
        $this->eventRetrieverMock->expects(self::once())
            ->method('getEventsWithLimit')
            ->willReturn($this->events);
        $this->eventStatusUpdaterMock->expects(self::exactly(2))
            ->method('updateStatus')
            ->withConsecutive([$eventIds, EventInterface::SENDING_STATUS], [$eventIds, EventInterface::SUCCESS_STATUS]);
        $this->scopeConfigMock->expects(self::never())
            ->method('getValue');
        $this->clientMock->expects(self::once())
            ->method('sendEventDataBatch')
            ->with(array_values($this->events))
            ->willReturn(new Response(200));
        $this->eventStatusUpdaterMock->expects(self::never())
            ->method('updateFailure');

        $this->batchSender->sendEventDataBatches();
    }

    /**
     * Tests failed sending of a batch of event data.
     *
     * @return void
     */
    public function testSendEventDataBatchFailure()
    {
        $eventIds = [1, 2];
        $this->lockManagerMock->expects(self::once())
            ->method('lock')
            ->willReturn(true);
        $this->eventRetrieverMock->expects(self::once())
            ->method('getEventsWithLimit')
            ->willReturn($this->events);
        $this->eventStatusUpdaterMock->expects(self::once())
            ->method('updateStatus')
            ->with($eventIds, EventInterface::SENDING_STATUS);
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->willReturn($this->maxRetries);
        $this->clientMock->expects(self::once())
            ->method('sendEventDataBatch')
            ->with(array_values($this->events))
            ->willReturn(new Response(400, [], '{"message": "msg"}'));
        $this->eventStatusUpdaterMock->expects(self::once())
            ->method('updateFailure')
            ->with($eventIds, $this->maxRetries);

        $this->batchSender->sendEventDataBatches();
    }

    /**
     * Tests failed sending of a batch of event data that is caused by issues with connecting to the events service.
     *
     * @return void
     */
    public function testSendEventDataBatchConnectionFailure()
    {
        $eventIds = [1, 2];
        $this->lockManagerMock->expects(self::once())
            ->method('lock')
            ->willReturn(true);
        $this->eventRetrieverMock->expects(self::once())
            ->method('getEventsWithLimit')
            ->willReturn($this->events);
        $this->eventStatusUpdaterMock->expects(self::once())
            ->method('updateStatus')
            ->with($eventIds, EventInterface::SENDING_STATUS);
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->willReturn($this->maxRetries);
        $this->clientMock->expects(self::once())
            ->method('sendEventDataBatch')
            ->willThrowException(new ConnectException("some error", new Request("POST", "")));
        $this->eventStatusUpdaterMock->expects(self::once())
            ->method('updateFailure')
            ->with($eventIds, $this->maxRetries, "Event publishing failed: some error");

        $this->batchSender->sendEventDataBatches();
    }
}
