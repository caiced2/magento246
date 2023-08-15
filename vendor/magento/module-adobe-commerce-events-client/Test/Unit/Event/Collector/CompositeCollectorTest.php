<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\Collector\CollectorInterface;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventData;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventDataFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\AdobeCommerceEventsClient\Event\Collector\CompositeCollector;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for the CompositeCollector class.
 */
class CompositeCollectorTest extends TestCase
{
    /**
     * @var CompositeCollector
     */
    private CompositeCollector $compositeCollector;

    /**
     * @var EventDataFactory|MockObject
     */
    private $eventDataFactory;

    /**
     * @var CacheInterface|MockObject
     */
    private $cacheMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var CollectorInterface|MockObject
     */
    private $collectorOneMock;

    /**
     * @var CollectorInterface|MockObject
     */
    private $collectorTwoMock;

    protected function setUp(): void
    {
        $this->eventDataFactory = $this->createMock(EventDataFactory::class);
        $this->cacheMock = $this->getMockForAbstractClass(CacheInterface::class);
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->collectorOneMock = $this->getMockForAbstractClass(CollectorInterface::class);
        $this->collectorTwoMock = $this->getMockForAbstractClass(CollectorInterface::class);

        $this->compositeCollector = new CompositeCollector(
            $this->eventDataFactory,
            $this->cacheMock,
            $this->serializerMock,
            [
                'collector_one' => $this->collectorOneMock,
                'collector_two' => $this->collectorTwoMock,
            ]
        );
    }

    public function testCollectNoCache(): void
    {
        $pathToModule = '/path/to/module';
        $eventDataOneMock = $this->createMock(EventData::class);
        $eventDataOneMock->expects(self::once())
            ->method('getData')
            ->willReturn(['data1']);
        $eventDataTwoMock = $this->createMock(EventData::class);
        $eventDataTwoMock->expects(self::once())
            ->method('getData')
            ->willReturn(['data2']);
        $this->cacheMock->expects(self::once())
            ->method('load')
            ->willReturn('');
        $this->serializerMock->expects(self::never())
            ->method('unserialize');
        $this->collectorOneMock->expects(self::once())
            ->method('collect')
            ->with($pathToModule)
            ->willReturn([
                'event1' => $eventDataOneMock
            ]);
        $this->collectorTwoMock->expects(self::once())
            ->method('collect')
            ->with($pathToModule)
            ->willReturn([
                'event2' => $eventDataTwoMock
            ]);
        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with([
                ['data1'],
                ['data2'],
            ])
            ->willReturn('serialized_string');
        $this->cacheMock->expects(self::once())
            ->method('save')
            ->with('serialized_string', 'composite_events_collector_collector_one_collector_two_/path/to/module');

        self::assertEquals(
            [
                'event1' => $eventDataOneMock,
                'event2' => $eventDataTwoMock,
            ],
            $this->compositeCollector->collect('/path/to/module')
        );
    }

    public function testCollectFromCache(): void
    {
        $this->collectorOneMock->expects(self::never())
            ->method('collect');
        $this->collectorTwoMock->expects(self::never())
            ->method('collect');
        $this->cacheMock->expects(self::never())
            ->method('save');
        $this->cacheMock->expects(self::once())
            ->method('load')
            ->with('composite_events_collector_collector_one_collector_two_/path/to/module')
            ->willReturn('serialized_string');
        $this->serializerMock->expects(self::once())
            ->method('unserialize')
            ->with('serialized_string')
            ->willReturn([
                [EventData::EVENT_NAME => 'data1'],
                [EventData::EVENT_NAME => 'data2']
            ]);
        $this->eventDataFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [[EventData::EVENT_NAME => 'data1']],
                [[EventData::EVENT_NAME => 'data2']],
            );

        $this->compositeCollector->collect('/path/to/module');
    }
}
