<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventData;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInfo;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoReflection;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for EventInfo class
 */
class EventInfoTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $eventCodeValidatorMock;

    /**
     * @var EventInfo
     */
    private EventInfo $eventInfo;

    /**
     * @var AggregatedEventList|MockObject
     */
    private $aggregatedEventList;

    /**
     * @var EventInfoReflection|MockObject
     */
    private $infoReflectionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->eventCodeValidatorMock = $this->getMockForAbstractClass(EventValidatorInterface::class);
        $this->aggregatedEventList = $this->createMock(AggregatedEventList::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->infoReflectionMock = $this->createMock(EventInfoReflection::class);

        $this->eventInfo = new EventInfo(
            $this->eventCodeValidatorMock,
            $this->infoReflectionMock,
            $this->aggregatedEventList,
            $this->loggerMock
        );
    }

    public function testWrongEventName(): void
    {
        $this->expectException(ValidatorException::class);

        $this->eventCodeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock)
            ->willThrowException(new ValidatorException(__('Wrong event prefix')));

        $this->eventInfo->getInfo($this->eventMock);
    }

    public function testObserverEventInfoNotFound(): void
    {
        $this->expectException(EventException::class);

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $this->aggregatedEventList->expects(self::once())
            ->method('getList')
            ->willReturn([]);

        $this->eventInfo->getInfo($this->eventMock);
    }

    public function testObserverEventInfo(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $eventDataMock = $this->createMock(EventData::class);

        $emitterClass = 'Path\To\Some\Class';
        $eventDataMock->expects(self::once())
            ->method('getEventClassEmitter')
            ->willReturn($emitterClass);
        $this->aggregatedEventList->expects(self::once())
            ->method('getList')
            ->willReturn([
                'observer.some_event_code' => $eventDataMock
            ]);
        $this->infoReflectionMock->expects(self::once())
            ->method('getInfoForObserverEvent')
            ->with($emitterClass, EventInfo::NESTED_LEVEL)
            ->willReturn(['id' => 1]);

        self::assertEquals(
            ['id' => 1],
            $this->eventInfo->getInfo($this->eventMock)
        );
    }

    public function testObserverEventInfoWithGetDataModelMethod(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $eventDataMock = $this->createMock(EventData::class);

        $emitterClass = 'Path\To\Some\Class';
        $eventDataMock->expects(self::once())
            ->method('getEventClassEmitter')
            ->willReturn($emitterClass);
        $this->aggregatedEventList->expects(self::once())
            ->method('getList')
            ->willReturn([
                'observer.some_event_code' => $eventDataMock
            ]);
        $this->infoReflectionMock->expects(self::once())
            ->method('getInfoForObserverEvent')
            ->with($emitterClass, EventInfo::NESTED_LEVEL)
            ->willReturn([
                'some_field' => 1,
                'data_model' => [
                    'entity_id' => 1,
                    'value' => 2,
                    'value2' => 3
                ]
            ]);

        self::assertEquals(
            [
                'entity_id' => 1,
                'value' => 2,
                'value2' => 3
            ],
            $this->eventInfo->getInfo($this->eventMock)
        );
    }

    public function testPluginEventInfoNotFound(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('magento.catalog.model.resource_model.categor.save');
        $this->expectException(EventException::class);
        $this->expectExceptionMessage('Cannot get details for event');

        $this->infoReflectionMock->expects(self::once())
            ->method('getPayloadInfo')
            ->with($this->eventMock, EventInfo::NESTED_LEVEL)
            ->willThrowException(new \ReflectionException("Error doing reflection"));

        $this->loggerMock->expects(self::once())
            ->method('error');
        $this->eventInfo->getInfo($this->eventMock);
    }

    public function testCategoryPluginEventInfo(): void
    {
        $eventName = 'plugin.magento.adobe_commerce_events_client.api.event_repository.save';
        $nestedLevel = 3;
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->infoReflectionMock->expects(self::once())
            ->method('getPayloadInfo')
            ->with($this->eventMock, $nestedLevel)
            ->willReturn([
                'id' => '1',
                'event_data' => 'test',
                'event_code' => 'test'
            ]);

        $info = $this->eventInfo->getInfo($this->eventMock, $nestedLevel);

        self::assertArrayHasKey('id', $info);
        self::assertArrayHasKey('event_data', $info);
        self::assertArrayHasKey('event_code', $info);
    }

    public function testCategoryPluginEventInfoWithDataModel(): void
    {
        $eventName = 'plugin.magento.adobe_commerce_events_client.api.event_repository.save';
        $nestedLevel = 3;
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->infoReflectionMock->expects(self::once())
            ->method('getPayloadInfo')
            ->with($this->eventMock, $nestedLevel)
            ->willReturn([
                'some_field' => 1,
                'data_model' => [
                    'entity_id' => 1,
                    'value' => 2,
                    'value2' => 3
                ]
            ]);

        $info = $this->eventInfo->getInfo($this->eventMock, $nestedLevel);

        self::assertArrayNotHasKey('some_field', $info);
        self::assertArrayHasKey('entity_id', $info);
        self::assertArrayHasKey('value', $info);
        self::assertArrayHasKey('value2', $info);
    }
}
