<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventRetriever;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Event\EventRetriever\CollectionToArrayConverter;
use Magento\AdobeCommerceEventsClient\Event\EventStatusUpdater;
use Magento\AdobeCommerceEventsClient\Model\Event;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see CollectionToArrayConverter class
 */
class CollectionToArrayConverterTest extends TestCase
{
    /**
     * @var CollectionToArrayConverter
     */
    private CollectionToArrayConverter $arrayConverter;

    /**
     * @var EventStatusUpdater|MockObject
     */
    private $eventStatusUpdaterMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    protected function setUp(): void
    {
        $this->eventStatusUpdaterMock = $this->createMock(EventStatusUpdater::class);
        $this->collectionMock = $this->createMock(Collection::class);

        $this->arrayConverter = new CollectionToArrayConverter($this->eventStatusUpdaterMock);
    }

    /**
     * Tests the converting of collection items to the array.
     *
     * @return void
     */
    public function testConvert()
    {
        $eventOneData = ['key' => 'test1'];
        $eventCodeOne = 'code1';
        $eventOneMetadata = [
            'commerceEdition' => 'Adobe Commerce',
            'commerceVersion' => '2.4.5',
            'eventsClientVersion' => '100.0.0'
        ];
        $eventOne = $this->createMock(Event::class);
        $eventOne->expects(self::once())
            ->method('getId')
            ->willReturn('1');
        $eventOne->expects(self::once())
            ->method('getEventData')
            ->willReturn($eventOneData);
        $eventOne->expects(self::once())
            ->method('getEventCode')
            ->willReturn($eventCodeOne);
        $eventOne->expects(self::once())
            ->method('getMetadata')
            ->willReturn($eventOneMetadata);

        $eventCodeTwo = 'code2';
        $eventTwoData = ['key' => 'test1'];
        $eventTwoMetadata = [
            'commerceEdition' => 'Adobe Commerce + B2B',
            'commerceVersion' => '2.4.5-p2',
            'eventsClientVersion' => '100.0.1'
        ];
        $eventTwo = $this->createMock(Event::class);
        $eventTwo->expects(self::once())
            ->method('getId')
            ->willReturn('2');
        $eventTwo->expects(self::once())
            ->method('getEventData')
            ->willReturn($eventTwoData);
        $eventTwo->expects(self::once())
            ->method('getEventCode')
            ->willReturn($eventCodeTwo);
        $eventTwo->expects(self::once())
            ->method('getMetadata')
            ->willReturn($eventTwoMetadata);
        $eventCodeThree = $this->createMock(Event::class);
        $eventCodeThree->expects(self::exactly(2))
            ->method('getId')
            ->willReturn('3');
        $eventCodeThree->expects(self::once())
            ->method('getEventData')
            ->willThrowException(new EventException(__('Failed to convert data')));
        $this->eventStatusUpdaterMock->expects(self::once())
            ->method('updateStatus')
            ->with(['3'], EventInterface::FAILURE_STATUS, 'Failed to process event data: Failed to convert data');

        $this->collectionMock->expects(self::once())
            ->method('getItems')
            ->willReturn([$eventOne, $eventTwo, $eventCodeThree]);

        $events = $this->arrayConverter->convert($this->collectionMock);
        $this->assertEquals(
            [
                '1' => ['eventCode' => $eventCodeOne, 'eventData' => $eventOneData, 'metadata' => $eventOneMetadata],
                '2' => ['eventCode' => $eventCodeTwo, 'eventData' => $eventTwoData, 'metadata' => $eventTwoMetadata]
            ],
            $events
        );
    }
}
