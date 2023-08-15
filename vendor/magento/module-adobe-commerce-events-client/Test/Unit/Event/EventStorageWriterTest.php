<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventMetadataCollector;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Queue\Publisher\EventPublisher;
use Magento\AdobeCommerceEventsClient\Event\Queue\Publisher\EventPublisherFactory;
use Magento\AdobeCommerceEventsClient\Model\Event as EventModel;
use Magento\AdobeCommerceEventsClient\Model\EventFactory as EventModelFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see EventStorageWriter class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventStorageWriterTest extends TestCase
{
    /**
     * @var EventStorageWriter
     */
    private EventStorageWriter $storageWriter;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var EventStorageWriter\CreateEventValidator|MockObject
     */
    private $validatorMock;

    /**
     * @var EventModelFactory|MockObject
     */
    private $eventModelFactoryMock;

    /**
     * @var DataFilterInterface|MockObject
     */
    private $dataFilterMock;

    /**
     * @var EventRepositoryInterface|MockObject
     */
    private $eventRepositoryMock;

    /**
     * @var EventMetadataCollector|MockObject
     */
    private $metadataCollectorMock;

    /**
     * @var EventPublisherFactory|MockObject
     */
    private $eventPublisherFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->eventListMock = $this->createPartialMock(EventList::class, ['getAll']);
        $this->validatorMock = $this->createMock(EventStorageWriter\CreateEventValidator::class);
        $this->eventRepositoryMock = $this->getMockForAbstractClass(EventRepositoryInterface::class);
        $this->eventModelFactoryMock = $this->createMock(EventModelFactory::class);
        $this->dataFilterMock = $this->getMockForAbstractClass(DataFilterInterface::class);
        $this->metadataCollectorMock = $this->createMock(EventMetadataCollector::class);
        $this->eventPublisherFactoryMock = $this->createMock(EventPublisherFactory::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->storageWriter = new EventStorageWriter(
            $this->eventListMock,
            $this->validatorMock,
            $this->eventRepositoryMock,
            $this->eventModelFactoryMock,
            $this->dataFilterMock,
            $this->metadataCollectorMock,
            $this->eventPublisherFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Tests that event is not saved in the case when validation failed.
     *
     * @return void
     */
    public function testCreateEventValidationFailed()
    {
        $eventCode = 'some_code';
        $eventData = [];

        $eventMock = $this->createEventMock($eventCode);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($eventMock, $eventData)
            ->willReturn(false);
        $this->eventModelFactoryMock->expects(self::never())
            ->method('create');

        $this->storageWriter->createEvent($eventCode, $eventData);
    }

    /**
     * Tests the saving of new event data in the case that the event data does not contain a key to be ignored.
     *
     * @return void
     */
    public function testCreateEvent()
    {
        $eventCode = "test.code";
        $eventCodeWithCommerce = EventSubscriberInterface::EVENT_PREFIX_COMMERCE . "test.code";
        $eventData = [
            "images" => [
                [
                    "id" => "1",
                    "file" => "image.jpg"
                ],
                [
                    "id" => "2",
                    "position" => "1"
                ]
            ]
        ];

        $eventMock = $this->createEventMock($eventCode);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);
        $eventMock->expects(self::exactly(2))
            ->method('isPriority')
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $eventModelMock = $this->createMock(EventModel::class);
        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($eventMock, $eventData)
            ->willReturn(true);
        $this->eventModelFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModelMock);
        $eventModelMock->expects(self::once())
            ->method('setEventCode')
            ->with($eventCodeWithCommerce);
        $eventModelMock->expects(self::once())
            ->method('setEventData')
            ->with($eventData);
        $eventModelMock->expects(self::once())
            ->method('setPriority')
            ->with(0);
        $this->dataFilterMock->expects(self::once())
            ->method('filter')
            ->with($eventCodeWithCommerce, $eventData)
            ->willReturn($eventData);
        $this->eventRepositoryMock->expects(self::once())
            ->method('save')
            ->with($eventModelMock);
        $this->loggerMock->expects(self::never())
            ->method('error');
        $this->eventPublisherFactoryMock->expects(self::never())
            ->method('create');

        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willReturn([
                'commerceEdition' => 'Adobe Commerce',
                'commerceVersion' => '2.4.5',
                'eventsClientVersion' => '100.0.0'
            ]);

        $this->storageWriter->createEvent($eventCode, $eventData);
    }

    /**
     * Tests the saving of new event data in the case that the event data contains a key to be ignored.
     * Tests that published is called for priority-type event.
     *
     * @return void
     */
    public function testCreateEventWithFilteredDataAndPriority()
    {
        $eventCode = "test.code";
        $eventCodeWithCommerce = EventSubscriberInterface::EVENT_PREFIX_COMMERCE . "test.code";
        $inputEventData = [
            'key1' => 'value1',
            'key2' => 'value1',
        ];
        $filteredEventData = [
            'key1' => 'value1',
        ];

        $eventMock = $this->createEventMock($eventCode);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);
        $eventMock->expects(self::exactly(2))
            ->method('isPriority')
            ->willReturn(true);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($eventMock, $inputEventData)
            ->willReturn(true);
        $eventModelMock = $this->createMock(EventModel::class);
        $this->eventModelFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModelMock);
        $eventModelMock->expects(self::once())
            ->method('setEventCode')
            ->with($eventCodeWithCommerce);
        $eventModelMock->expects(self::once())
            ->method('setEventData')
            ->with($filteredEventData);
        $eventModelMock->expects(self::once())
            ->method('getId')
            ->willReturn('123');
        $this->eventRepositoryMock->expects(self::once())
            ->method('save')
            ->with($eventModelMock);
        $this->dataFilterMock->expects(self::once())
            ->method('filter')
            ->with($eventCodeWithCommerce, $inputEventData)
            ->willReturn($filteredEventData);
        $eventPublisherMock = $this->createMock(EventPublisher::class);
        $this->eventPublisherFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventPublisherMock);
        $eventPublisherMock->expects(self::once())
            ->method('execute')
            ->with('123');
        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willReturn([
                'commerceEdition' => 'Adobe Commerce',
                'commerceVersion' => '2.4.5',
                'eventsClientVersion' => '100.0.0'
            ]);

        $this->storageWriter->createEvent($eventCode, $inputEventData);
    }

    /**
     * Tests that saveEvent method is called for each event that registered as alias.
     * Tests that not enabled events are skipped.
     *
     * @return void
     */
    public function testEventHasAliases()
    {
        $eventData = ['key' => 'value'];
        $eventCodeOne = "test.code.one";
        $eventCodeTwo = "test.code.two";
        $eventCodeThree = "test.code.three";
        $eventMockOne = $this->createMock(Event::class);
        $eventMockTwo = $this->createMock(Event::class);
        $eventMockThree = $this->createMock(Event::class);
        $eventMockOne->expects(self::never())
            ->method('getName')
            ->willReturn($eventCodeOne);
        $eventMockOne->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $eventMockOne->expects(self::never())
            ->method('isBasedOn');
        $eventMockTwo->expects(self::once())
            ->method('getName')
            ->willReturn($eventCodeTwo);
        $eventMockTwo->expects(self::once())
            ->method('isBasedOn')
            ->willReturn(true);
        $eventMockTwo->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMockThree->expects(self::once())
            ->method('getName')
            ->willReturn($eventCodeThree);
        $eventMockThree->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMockThree->expects(self::once())
            ->method('isBasedOn')
            ->willReturn(true);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMockOne, $eventMockTwo, $eventMockThree]);

        $this->validatorMock->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$eventMockTwo, $eventData],
                [$eventMockThree, $eventData],
            )
            ->willReturn(true);

        $this->storageWriter->createEvent($eventCodeOne, $eventData);
    }

    /**
     * Tests that an event is not saved when the event code input to createEvent is not based on custom
     * event code.
     *
     * @return void
     */
    public function testEventNotSaved()
    {
        $eventCode = 'observer.some_code';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMock->expects(self::once())
            ->method('isBasedOn')
            ->with($eventCode)
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $this->validatorMock->expects(self::never())
            ->method('validate');

        $this->storageWriter->createEvent($eventCode, []);
    }

    /**
     * @param string $eventCode
     * @return MockObject
     */
    private function createEventMock(string $eventCode): MockObject
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMock->expects(self::once())
            ->method('isBasedOn')
            ->with($eventCode)
            ->willReturn(true);

        return $eventMock;
    }
}
