<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\Collector\EventData;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventDataFactory;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventMethodCollector;
use Magento\AdobeCommerceEventsClient\Event\Collector\MethodFilter;
use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for EventMethodCollector class
 */
class EventMethodCollectorTest extends TestCase
{
    /**
     * @var EventMethodCollector
     */
    private EventMethodCollector $eventMethodCollector;

    /**
     * @var EventDataFactory|MockObject
     */
    private $eventDataFactoryMock;

    /**
     * @var EventCodeConverter|MockObject
     */
    private $eventCodeConverterMock;

    /**
     * @var MethodFilter|MockObject
     */
    private $methodFilterMock;

    protected function setUp(): void
    {
        $this->eventDataFactoryMock = $this->createMock(EventDataFactory::class);
        $this->eventCodeConverterMock = $this->createMock(EventCodeConverter::class);
        $this->methodFilterMock = $this->createMock(MethodFilter::class);

        $this->eventMethodCollector = new EventMethodCollector(
            $this->eventDataFactoryMock,
            $this->eventCodeConverterMock,
            $this->methodFilterMock
        );
    }

    /**
     * Tests that events correctly collected from the reflection class
     *
     * @return void
     */
    public function testCollect(): void
    {
        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getName')
            ->willReturn('Some\Class\Name');
        $reflectionMethodMock1 = $this->createReflectionMethodMock('method1');
        $reflectionMethodMock2 = $this->createReflectionMethodMock('method2');
        $reflectionMethodMock3 = $this->createReflectionMethodMock('method3');

        $this->methodFilterMock->expects(self::exactly(3))
            ->method('isExcluded')
            ->withConsecutive(
                ['method1'],
                ['method2'],
                ['method3'],
            )
            ->willReturnOnConsecutiveCalls(true, false, false);
        $this->eventCodeConverterMock->expects(self::exactly(2))
            ->method('convertToEventName')
            ->withConsecutive(
                ['Some\Class\Name', 'method2'],
                ['Some\Class\Name', 'method3'],
            )
            ->willReturnOnConsecutiveCalls(
                'some.class.name.method2',
                'some.class.name.method3',
            );
        $reflectionClassMock->expects(self::once())
            ->method('getMethods')
            ->willReturn([
                $reflectionMethodMock1,
                $reflectionMethodMock2,
                $reflectionMethodMock3,
            ]);
        $eventDataMock1 = $this->createMock(EventData::class);
        $eventDataMock2 = $this->createMock(EventData::class);
        $this->eventDataFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [[
                    'event_name' => 'plugin.some.class.name.method2',
                    'event_class_emitter' => 'Some\Class\Name'
                ]],
                [[
                    'event_name' => 'plugin.some.class.name.method3',
                    'event_class_emitter' => 'Some\Class\Name'
                ]],
            )
            ->willReturnOnConsecutiveCalls(
                $eventDataMock1,
                $eventDataMock2
            );

        self::assertEquals(
            [
                'plugin.some.class.name.method2' => $eventDataMock1,
                'plugin.some.class.name.method3' => $eventDataMock2
            ],
            $this->eventMethodCollector->collect($reflectionClassMock)
        );
    }

    /**
     * @param string $name
     * @return MockObject
     */
    private function createReflectionMethodMock(string $name): MockObject
    {
        $reflectionMethodMock = $this->createMock(ReflectionMethod::class);
        $reflectionMethodMock->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        return $reflectionMethodMock;
    }
}
