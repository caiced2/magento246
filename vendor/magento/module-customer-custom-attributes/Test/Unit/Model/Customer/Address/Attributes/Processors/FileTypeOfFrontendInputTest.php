<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Address\Attributes\Processors;

use Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\Processors\FileTypeOfFrontendInput;
use Magento\Framework\Api\AttributeInterface;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;
use Magento\Framework\Api\MetadataObjectInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests FileTypeOfFrontendInput processor.
 */
class FileTypeOfFrontendInputTest extends TestCase
{
    /**
     * @var FileTypeOfFrontendInput
     */
    private $model;

    /**
     * @var CustomAttributeListInterface|MockObject
     */
    private $attributeListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->attributeListMock = $this->getMockBuilder(CustomAttributeListInterface::class)
            ->getMock();

        $this->model = new FileTypeOfFrontendInput($this->attributeListMock);
    }

    /**
     * Tests case when incoming attribute is absent in attribute list.
     *
     * @return void
     */
    public function testWhenAttributeIsAbsentInAttributeList(): void
    {
        $attribute = $this->createAttributeMock('absent_code', false);
        $this->attributeListMock->method('getAttributes')
            ->willReturn([]);

        $this->model->process($attribute);
    }

    /**
     * Tests case when incoming attribute is not file type of frontend input.
     *
     * @return void
     */
    public function testNonFileTypeOfFrontendInput(): void
    {
        $attribute = $this->createAttributeMock('non_file_code', false);
        $fileAttributeMetaData = $this->getMockBuilder(MetadataObjectInterface::class)
            ->onlyMethods(['getAttributeCode', 'setAttributeCode'])
            ->addMethods(['getFrontendInput'])
            ->getMock();
        $fileAttributeMetaData->method('getFrontendInput')
            ->willReturn('text');
        $this->attributeListMock->method('getAttributes')
            ->willReturn(['non_file_code' => $fileAttributeMetaData]);

        $this->model->process($attribute);
    }

    /**
     * Tests case when incoming attribute is type of file and it's value has a target format.
     *
     * @param string $frontendType
     * @return void
     * @dataProvider fileTypeAttributeValueWillChangeDataProvider
     */
    public function testFileTypeAttributeValueWillChange(string $frontendType): void
    {
        $targetValue = '/f/1/somedocument.doc';
        $incomingValue = [
            'value' => [
                [
                    'file' => $targetValue,
                    'file_size' => 12312,
                ],
            ],
        ];
        $fileAttribute = $this->createAttributeMock(
            'document',
            true,
            $incomingValue,
            $targetValue
        );

        $fileAttributeMetaData = $this->getMockBuilder(MetadataObjectInterface::class)
            ->onlyMethods(['getAttributeCode', 'setAttributeCode'])
            ->addMethods(['getFrontendInput'])
            ->getMock();
        $fileAttributeMetaData->method('getAttributeCode')
            ->willReturn('document');
        $fileAttributeMetaData->method('getFrontendInput')
            ->willReturn($frontendType);
        $this->attributeListMock->method('getAttributes')
            ->willReturn(['document' => $fileAttributeMetaData]);

        $this->model->process($fileAttribute);
    }

    /**
     * @return array
     */
    public function fileTypeAttributeValueWillChangeDataProvider(): array
    {
        return [
            ['file'],
            ['image']
        ];
    }

    /**
     * Creates attribute mock.
     *
     * @param string $code Attribute code
     * @param bool $expectationChangingValue Whether the attribute's value would be changed
     * @param mixed $value Attribute incoming value
     * @param mixed $changedValue Attribute changed value
     * @return AttributeInterface|MockObject
     */
    private function createAttributeMock(
        string $code,
        bool $expectationChangingValue,
        $value = null,
        $changedValue = null
    ) {
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->method('getAttributeCode')
            ->willReturn($code);
        $attribute->method('getValue')
            ->willReturn($value);

        $expect = $expectationChangingValue ? $this->once() : $this->never();
        $attribute->expects($expect)
            ->method('setValue')
            ->with($changedValue);

        return $attribute;
    }
}
