<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Util;

use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EventCodeConverter class.
 */
class EventCodeConverterTest extends TestCase
{
    /**
     * @var EventCodeConverter
     */
    private EventCodeConverter $converter;

    public function setUp(): void
    {
        $this->converter = new EventCodeConverter();
    }

    /**
     * Tests conversion of an event code to a FQCN class name.
     *
     * @param string $eventCode
     * @param string $expectedFqcn
     * @return void
     * @dataProvider convertToFqcnDataProvider
     */
    public function testConvertToFqcn(string $eventCode, string $expectedFqcn): void
    {
        self::assertEquals($expectedFqcn, $this->converter->convertToFqcn($eventCode));
    }

    /**
     * @return array
     */
    public function convertToFqcnDataProvider(): array
    {
        return[
            ['plugin.magento.theme.api.design_config_repository.save', 'Magento\Theme\Api\DesignConfigRepository'],
            ['plugin.magento.rule.model.resource_model.rule.save', 'Magento\Rule\Model\ResourceModel\Rule'],
        ];
    }

    /**
     * Tests extraction of a method name from an event code.
     *
     * @param string $eventCode
     * @param string $expectedMethodName
     * @return void
     * @dataProvider extractMethodNameDataProvider
     */
    public function testExtractMethodName(string $eventCode, string $expectedMethodName): void
    {
        self::assertEquals($expectedMethodName, $this->converter->extractMethodName($eventCode));
    }

    /**
     * @return array
     */
    public function extractMethodNameDataProvider(): array
    {
        return[
            ['plugin.magento.catalog.resource_model.product.save', 'save'],
            ['magento.eav.api.attribute_repository.delete_by_id', 'deleteById'],
            ['magento.eav.api.attribute_repository.delete', 'delete'],
        ];
    }
}
