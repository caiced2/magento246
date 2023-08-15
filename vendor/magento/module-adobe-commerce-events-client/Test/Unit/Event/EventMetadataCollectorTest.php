<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\EventMetadataCollector;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataException;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataInterface;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EventMetadataCollector class
 */
class EventMetadataCollectorTest extends TestCase
{
    /**
     * @var EventMetadataCollector
     */
    private EventMetadataCollector $metadataCollector;

    /**
     * @var EventMetadataPool|MockObject
     */
    private $eventMetadataPoolMock;

    protected function setUp(): void
    {
        $this->eventMetadataPoolMock = $this->createMock(EventMetadataPool::class);

        $this->metadataCollector = new EventMetadataCollector(
            $this->eventMetadataPoolMock
        );
    }

    /**
     * Test loading methods are only called once, and appropriate data transformations are applied
     *
     * @return void
     * @throws EventMetadataException
     */
    public function testGetMetadata(): void
    {
        $metadataMockOne = $this->getMockForAbstractClass(EventMetadataInterface::class);
        $metadataMockOne->expects(self::once())
            ->method('get')
            ->willReturn(['key1' => 'value1']);
        $metadataMockTwo = $this->getMockForAbstractClass(EventMetadataInterface::class);
        $metadataMockTwo->expects(self::once())
            ->method('get')
            ->willReturn(['key2' => 'value2']);
        $this->eventMetadataPoolMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                $metadataMockOne,
                $metadataMockTwo,
            ]);

        for ($i = 0; $i < 2; $i++) {
            $metadata = $this->metadataCollector->getMetadata();
            $this->assertArrayHasKey('key1', $metadata);
            $this->assertArrayHasKey('key2', $metadata);
            $this->assertEquals('value1', $metadata['key1']);
            $this->assertEquals('value2', $metadata['key2']);
        }
    }
}
