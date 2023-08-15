<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventInfo;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoReflection;
use Magento\AdobeCommerceEventsClient\Util\ClassToArrayConverter;
use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use Magento\AdobeCommerceEventsClient\Util\ReflectionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class EventInfoReflectionTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var ReflectionHelper|MockObject
     */
    private $reflectionHelperMock;

    /**
     * @var ClassToArrayConverter|MockObject
     */
    private $classToArrayConverterMock;

    /**
     * @var EventInfoReflection
     */
    private EventInfoReflection $eventInfoReflection;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->reflectionHelperMock = $this->createMock(ReflectionHelper::class);
        $this->classToArrayConverterMock = $this->createMock(ClassToArrayConverter::class);

        $this->eventInfoReflection = new EventInfoReflection(
            $this->reflectionHelperMock,
            $this->classToArrayConverterMock,
            new EventCodeConverter()
        );
    }

    public function testObserverEventInfo(): void
    {
        $eventClassEmitter = 'Path\To\Some\Class';
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with($eventClassEmitter)
            ->willReturn(['id' => 1]);

        self::assertEquals(
            ['id' => 1],
            $this->eventInfoReflection->getInfoForObserverEvent($eventClassEmitter)
        );
    }

    public function testPluginEventInfoNotFound(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('magento.catalog.model.resource_model.categor.save');
        $this->expectException(ReflectionException::class);

        $this->eventInfoReflection->getPayloadInfo($this->eventMock);
    }

    public function testPayloadInfo(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('plugin.magento.adobe_commerce_events_client.api.event_repository.save');
        $returnType = 'Magento\AdobeCommerceEventsClient\Api\Data\EventInterface';
        $this->reflectionHelperMock->expects(self::once())
            ->method('getReturnType')
            ->willReturn($returnType);
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with($returnType, 3)
            ->willReturn([
                'id' => '1',
                'event_data' => 'test',
                'event_code' => 'test',
            ]);

        $info = $this->eventInfoReflection->getPayloadInfo($this->eventMock, 3);

        self::assertArrayHasKey('id', $info);
        self::assertArrayHasKey('event_data', $info);
        self::assertArrayHasKey('event_code', $info);
    }
}
