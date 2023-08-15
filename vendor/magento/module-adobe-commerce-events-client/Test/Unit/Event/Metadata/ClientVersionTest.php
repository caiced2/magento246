<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Metadata;

use Magento\AdobeCommerceEventsClient\Event\Metadata\ClientVersion;
use Magento\Framework\Module\PackageInfo;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ClientVersion class
 */
class ClientVersionTest extends TestCase
{
    public function testGet()
    {
        $packageInfoMock = $this->createMock(PackageInfo::class);
        $packageInfoMock->expects(self::once())
            ->method('getVersion')
            ->with('Magento_AdobeCommerceEventsClient')
            ->willReturn('1.0.0');

        $metadata = (new ClientVersion($packageInfoMock))->get();

        self::assertEquals(1, count($metadata));
        self::assertEquals('1.0.0', $metadata['eventsClientVersion']);
    }
}
