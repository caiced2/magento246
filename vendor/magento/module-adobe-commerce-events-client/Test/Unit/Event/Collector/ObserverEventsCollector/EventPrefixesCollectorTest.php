<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector\ObserverEventsCollector;

use ReflectionClass;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventDataFactory;
use Magento\AdobeCommerceEventsClient\Event\Collector\NameFetcher;
use Magento\AdobeCommerceEventsClient\Event\Collector\ObserverEventsCollector\EventPrefixesCollector;
use Magento\Framework\App\Utility\ReflectionClassFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SplFileInfo;
use Exception;

/**
 * Tests for the @see EventPrefixesCollector Class
 */
class EventPrefixesCollectorTest extends TestCase
{
    /**
     * @var EventDataFactory|MockObject
     */
    private $eventDataFactoryMock;

    /**
     * @var ReflectionClassFactory|MockObject
     */
    private $reflectionClassFactoryMock;

    /**
     * @var EventPrefixesCollector
     */
    private EventPrefixesCollector $eventPrefixesCollector;

    protected function setUp(): void
    {
        $this->eventDataFactoryMock = $this->createMock(EventDataFactory::class);
        $this->reflectionClassFactoryMock = $this->createMock(ReflectionClassFactory::class);

        $this->eventPrefixesCollector = new EventPrefixesCollector(
            new NameFetcher(),
            $this->eventDataFactoryMock,
            $this->reflectionClassFactoryMock
        );
    }

    /**
     * @dataProvider eventFetcherDataProvider
     * @throws Exception
     */
    public function testEventFetcher(string $filename, string $className): void
    {
        $fileContent = file_get_contents(__DIR__ . '/_files/' . $filename);

        $refClassMock = $this->createMock(ReflectionClass::class);
        $refClassMock->expects(self::once())
            ->method('isSubclassOf')
            ->willReturn(true);
        $this->reflectionClassFactoryMock->expects(self::once())
            ->method('create')
            ->with($className)
            ->willReturn($refClassMock);
        $this->eventDataFactoryMock->expects(self::exactly(4))
            ->method('create');

        $events = $this->eventPrefixesCollector->fetchEvents($this->createMock(SplFileInfo::class), $fileContent);

        self::assertEquals(4, count($events));
        self::assertArrayHasKey('observer.sample_class_save_commit_after', $events);
        self::assertArrayHasKey('observer.sample_class_save_after', $events);
        self::assertArrayHasKey('observer.sample_class_delete_after', $events);
        self::assertArrayHasKey('observer.sample_class_delete_commit_after', $events);
    }

    /**
     * @return array
     */
    public function eventFetcherDataProvider(): array
    {
        return [
            [
                'sample_code_event_prefixes.php',
                'Magento\Framework\Module\SampleClass'
            ],
            [
                'sample_code_event_prefixes_double_quotes.php',
                'Magento\Framework\Module\SampleClassDoubleQuotes'
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function testEventFetcherNotSubclassOfAbstractModel(): void
    {
        $fileContent = file_get_contents(__DIR__ . '/_files/sample_code_event_prefixes.php');

        $refClassMock = $this->createMock(ReflectionClass::class);
        $refClassMock->expects(self::once())
            ->method('isSubclassOf')
            ->willReturn(false);
        $this->reflectionClassFactoryMock->expects(self::once())
            ->method('create')
            ->with('Magento\Framework\Module\SampleClass')
            ->willReturn($refClassMock);
        $this->eventDataFactoryMock->expects(self::never())
            ->method('create');

        $events = $this->eventPrefixesCollector->fetchEvents($this->createMock(SplFileInfo::class), $fileContent);

        self::assertEquals(0, count($events));
    }
}
