<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Setup;

use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Setup\ReportConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReportConverterTest extends TestCase
{
    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var ReportConverter
     */
    private $serializedReportToJson;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->getMock();
        $this->serializedReportToJson = $objectManager->getObject(
            ReportConverter::class,
            [
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * Test report data converter
     *
     * @return void
     */
    public function testConvert()
    {
        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $data = new Phrase('text');

        // phpcs:ignore
        $serializedData = serialize(['data' => $data]);
        $jsonData = json_encode(['data' => $data]);

        $this->assertEquals($jsonData, $this->serializedReportToJson->convert($serializedData));
    }

    public function testConvertCorruptedData()
    {
        $this->expectException('Magento\Framework\DB\DataConverter\DataConversionException');
        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $serialized = 'O:8:"stdClass":1:{s:8:"property"';
        $this->serializedReportToJson->convert($serialized);
    }

    /**
     * Test skipping deserialization and json_encoding of valid JSON encoded string
     */
    public function testSkipJsonDataConversion()
    {
        $serialized = '{"property":1}';
        $this->jsonMock->expects($this->never())->method('serialize');
        $this->serializedReportToJson->convert($serialized);
    }
}
