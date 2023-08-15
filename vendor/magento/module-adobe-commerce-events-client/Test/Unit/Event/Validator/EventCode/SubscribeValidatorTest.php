<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\SubscribeValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SubscribeValidator class
 */
class SubscribeValidatorTest extends TestCase
{
    /**
     * @var SubscribeValidator
     */
    private SubscribeValidator $validator;

    /**
     * @var AggregatedEventList|MockObject
     */
    private $aggregatedEventListMock;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->aggregatedEventListMock = $this->createMock(AggregatedEventList::class);
        $this->eventListMock = $this->createMock(EventList::class);

        $this->validator = new SubscribeValidator($this->aggregatedEventListMock, $this->eventListMock);
    }

    public function testNotPluginEventType()
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $this->eventListMock->expects(self::never())
            ->method('getAll');

        $this->validator->validate($this->eventMock);
    }

    public function testEventIsAlreadySubscribed()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Event is already subscribed');

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.some_event_code');
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('isOptional')
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                'plugin.some_event_code' => $eventMock
            ]);

        $this->validator->validate($this->eventMock);
    }

    public function testCanSubscribe()
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.some_event_code');
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('isOptional')
            ->willReturn(true);
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                'plugin.some_event_code' => $eventMock
            ]);

        $this->validator->validate($this->eventMock);
    }

    public function testEventIsNotRegistered()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Could not register event "plugin.some_event_code"');

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.some_event_code');
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([]);

        $this->validator->validate($this->eventMock);
    }

    public function testEventIsNotRegisteredForce()
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.some_event_code');
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([]);

        $this->validator->validate($this->eventMock, true);
    }

    public function testRuleBasedEvent()
    {
        $this->eventMock->expects(self::exactly(3))
            ->method('getParent')
            ->willReturn('plugin.some_event_code');
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.rule_based_event_code');

        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([]);

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('isOptional');
        $eventMock->expects(self::never())
            ->method('isEnabled');
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                'plugin.some_event_code' => $eventMock
            ]);

        $this->validator->validate($this->eventMock);
    }

    public function testRuleBasedEventSubscribeFailure()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Could not register event "plugin.some_event_code"');

        $this->eventMock->expects(self::exactly(2))
            ->method('getParent')
            ->willReturn('plugin.some_event_code');
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.rule_based_event_code');

        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([]);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([]);

        $this->validator->validate($this->eventMock);
    }

    public function testRuleBasedEventInvalidName()
    {
        $eventCode = 'plugin.rule_based_event_code';
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('"plugin.rule_based_event_code" cannot be used as the event code');

        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('plugin.some_event_code');
        $this->eventMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($eventCode);
        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([
                $eventCode => []
            ]);

        $this->validator->validate($this->eventMock);
    }
}
