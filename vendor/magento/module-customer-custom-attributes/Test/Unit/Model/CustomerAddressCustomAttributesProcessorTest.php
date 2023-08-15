<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model;

use Magento\Framework\Api\AttributeInterface;
use Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\ProcessorComponentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\CustomerCustomAttributes\Model\CustomerAddressCustomAttributesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test process custom customer attributes before saving address
 */
class CustomerAddressCustomAttributesProcessorTest extends TestCase
{
    /**
     * @var CustomerAddressCustomAttributesProcessor
     */
    private $model;

    /**
     * @var AddressInterface|MockObject
     */
    private $addressMock;

    /**
     * @var ProcessorComponentInterface[]|MockObject[]
     */
    private $processors = [];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->addressMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->processors[] = $this->createMock(ProcessorComponentInterface::class);
        $this->processors[] = $this->createMock(ProcessorComponentInterface::class);

        $this->model = new CustomerAddressCustomAttributesProcessor($this->processors);
    }

    /**
     * Tests that each processor will be executed for each attribute.
     *
     * @return void
     */
    public function testEachProcessorExecute(): void
    {
        $attributes = [];
        $attributes[] = $this->createMock(AttributeInterface::class);
        $attributes[] = $this->createMock(AttributeInterface::class);
        $this->addressMock->method('getCustomAttributes')
            ->willReturn($attributes);

        $attributesCount = count($attributes);
        $consecutiveArray = [];
        foreach($attributes as $attribute) {
            $consecutiveArray[] = [$attribute];
        }

        foreach($this->processors as $processor) {
            $processor->expects($this->exactly($attributesCount))
                ->method('process')
                ->withConsecutive(...$consecutiveArray);
        }

        $this->model->execute($this->addressMock);
    }

    /**
     * Tests that incoming attribute object won't be processed repeatedly.
     *
     * @return void
     */
    public function testAttributeWontBeProcessedRepeatedly(): void
    {
        $attribute = $this->createMock(AttributeInterface::class);
        $this->addressMock->method('getCustomAttributes')
            ->willReturn([$attribute]);

        $this->model->execute($this->addressMock);

        foreach($this->processors as $processor) {
            $processor->expects($this->never())
                ->method('process');
        }

        $this->model->execute($this->addressMock);
    }
}
