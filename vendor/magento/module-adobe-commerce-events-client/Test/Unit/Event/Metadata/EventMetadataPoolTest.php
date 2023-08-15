<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Metadata;

use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataInterface;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataPool;
use Magento\Framework\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventMetadataPool class
 */
class EventMetadataPoolTest extends TestCase
{
    public function testWrongInstanceGiven()
    {
        $this->expectException(InvalidArgumentException::class);

        new EventMetadataPool([
            $this->createMock(EventMetadataInterface::class),
            $this->createMock(EventMetadataPool::class)
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetAll()
    {
        $eventMetadataPool = new EventMetadataPool([
            $this->createMock(EventMetadataInterface::class),
            $this->createMock(EventMetadataInterface::class),
        ]);

        self::assertEquals(2, count($eventMetadataPool->getAll()));
    }
}
