<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Plugin;

use Magento\CustomerCustomAttributes\Helper\Data;
use Magento\CustomerCustomAttributes\Model\Plugin\ConvertQuoteAddressToOrderAddress;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrderAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConvertQuoteAddressToOrderAddressTest extends TestCase
{
    /**
     * @var ConvertQuoteAddressToOrderAddress
     */
    private $model;

    /**
     * @var MockObject
     */
    private $customerDataMock;

    protected function setUp(): void
    {
        $this->customerDataMock = $this->createMock(Data::class);
        $this->model = new ConvertQuoteAddressToOrderAddress($this->customerDataMock);
    }

    public function testAfterConvert()
    {
        $attribute = 'attribute';
        $attributeValue = 'attributeValue';
        $quoteAddressMock = $this->createMock(Address::class);
        $orderAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);

        $this->customerDataMock->expects($this->once())
            ->method('getCustomerAddressUserDefinedAttributeCodes')
            ->willReturn([$attribute]);

        $quoteAddressMock->expects($this->once())->method('getData')->with($attribute)->willReturn($attributeValue);
        $orderAddressMock->expects($this->once())
            ->method('setData')
            ->with($attribute, $attributeValue)
            ->willReturnSelf();

        $result = $this->model->afterConvert(
            $this->createMock(ToOrderAddress::class),
            $orderAddressMock,
            $quoteAddressMock
        );

        $this->assertEquals($orderAddressMock, $result);
    }
}
