<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Address\Attributes\Processors;

use Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\Processors\ArrayTypeOfValue;
use Magento\Framework\Api\AttributeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests ArrayTypeOfValue processor.
 */
class ArrayTypeOfValueTest extends TestCase
{
    /**
     * @var ArrayTypeOfValue
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->model = new ArrayTypeOfValue();
    }

    /**
     * Tests case when attribute is required to transform and so it will change
     *
     * @return void
     */
    public function testAttributeProcess(): void
    {
        $targetValue = 'some value';
        $incomingValue = ['value' => $targetValue];
        $attribute = $this->createAttributeMock(
            'attr_code',
            true,
            $incomingValue,
            $targetValue
        );

        $this->model->process($attribute);
    }

    /**
     * Tests case when attribute has not signs and so it won't change
     *
     * @return void
     */
    public function testAttributeWhenTransformationIsNotRequired(): void
    {
        $targetValue = 'some value';
        $incomingValue = $targetValue;
        $attribute = $this->createAttributeMock(
            'attr_code',
            false,
            $incomingValue,
            $targetValue
        );

        $this->model->process($attribute);
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
