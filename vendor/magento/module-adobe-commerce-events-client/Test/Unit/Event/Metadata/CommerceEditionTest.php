<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Metadata;

use Magento\AdobeCommerceEventsClient\Event\Metadata\CommerceEdition;
use Magento\Framework\App\ProductMetadataInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see CommerceEdition class
 */
class CommerceEditionTest extends TestCase
{
    /**
     * @return void
     * @dataProvider getDataProvider
     */
    public function testGet(string $edition, string $expectedEdition)
    {
        $commerceMetadataMock = $this->getMockForAbstractClass(ProductMetadataInterface::class);
        $commerceMetadataMock->expects(self::once())
            ->method('getEdition')
            ->willReturn($edition);
        $commerceMetadataMock->expects(self::once())
            ->method('getVersion')
            ->willReturn('2.4.6');

        $metadata = (new CommerceEdition($commerceMetadataMock))->get();

        self::assertEquals(2, count($metadata));
        self::assertEquals($expectedEdition, $metadata['commerceEdition']);
        self::assertEquals('2.4.6', $metadata['commerceVersion']);
    }

    public function getDataProvider(): array
    {
        return [
            [
                'Commerce',
                'Adobe Commerce',
            ],
            [
                'Community',
                'Open Source',
            ],
            [
                'B2B',
                'Adobe Commerce + B2B',
            ],
        ];
    }
}
