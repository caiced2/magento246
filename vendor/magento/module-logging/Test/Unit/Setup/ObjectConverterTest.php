<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Setup;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize as Serializer;
use Magento\Logging\Setup\ObjectConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectConverterTest extends TestCase
{
    /**
     * @var ObjectConverter
     */
    protected $model;

    /**
     * @var Serializer|MockObject
     */
    private $serializeMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    protected function setUp(): void
    {
        $this->serializeMock = $this->createMock(Serializer::class);
        $this->jsonMock = $this->createPartialMock(Json::class, ['serialize']);
        $this->model = new ObjectConverter($this->serializeMock, $this->jsonMock);
    }

    /**
     * Init serializer mock with default serialize and unserialize callbacks
     */
    protected function initSerializerMock()
    {
        $this->serializeMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    // phpcs:ignore
                    return unserialize($value);
                }
            );
        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
    }

    /**
     * Test that object is converted
     */
    public function testConvertObjects()
    {
        $this->initSerializerMock();
        $this->assertEquals('{"property":1}', $this->model->convert('O:8:"stdClass":1:{s:8:"property";i:1;}'));
    }

    public function testConvertCorruptedData()
    {
        $this->expectException('Magento\Framework\DB\DataConverter\DataConversionException');
        $this->initSerializerMock();
        $serialized = 'O:8:"stdClass":1:{s:8:"property"';
        $this->model->convert($serialized);
    }

    /**
     * Test skipping deserialization and json_encoding of valid JSON encoded string
     */
    public function testSkipJsonDataConversion()
    {
        $serialized = '{"property":1}';
        $this->serializeMock->expects($this->never())->method('unserialize');
        $this->jsonMock->expects($this->never())->method('serialize');
        $this->model->convert($serialized);
    }
}
