<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector\ObserverEventsCollector;

use Magento\AdobeCommerceEventsClient\Event\Collector\EventDataFactory;
use Magento\AdobeCommerceEventsClient\Event\Collector\NameFetcher;
use Magento\AdobeCommerceEventsClient\Event\Collector\ObserverEventsCollector\DispatchMethodCollector;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SplFileInfo;

/**
 * Tests for the @see DispatchMethodCollector Class
 */
class DispatchMethodCollectorTest extends TestCase
{
    /**
     * @var NameFetcher|MockObject
     */
    private $nameFetcherMock;
    
    /**
     * @var EventDataFactory|MockObject
     */
    private $eventDataFactoryMock;

    /**
     * @var DispatchMethodCollector
     */
    private DispatchMethodCollector $dispatchMethodCollector;

    protected function setUp(): void
    {
        $this->nameFetcherMock = $this->createMock(NameFetcher::class);
        $this->eventDataFactoryMock = $this->createMock(EventDataFactory::class);

        $this->dispatchMethodCollector = new DispatchMethodCollector(
            $this->nameFetcherMock,
            $this->eventDataFactoryMock
        );
    }

    /**
     * @throws LocalizedException
     */
    public function testEventFetcher(): void
    {
        $fileContent = file_get_contents(__DIR__ . '/_files/sample_code_method_dispatch.php');

        $events = $this->dispatchMethodCollector->fetchEvents($this->createMock(SplFileInfo::class), $fileContent);

        self::assertEquals(4, count($events));
        self::assertArrayNotHasKey('event_single_quotes_dynamic_', $events);
        self::assertArrayNotHasKey('event_single_quotes_dynamic_multiple_lines_', $events);
        self::assertArrayHasKey('observer.event_single_quotes', $events);
        self::assertArrayHasKey('observer.event_double_quotes', $events);
        self::assertArrayHasKey('observer.event_single_quotes_multiple_lines', $events);
        self::assertArrayHasKey('observer.event_double_quotes_multiple_lines', $events);
    }
}
