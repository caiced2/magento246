<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Plugin;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\CustomerCustomAttributes\Helper\Data;
use Magento\CustomerCustomAttributes\Model\Plugin\ConvertQuoteAddressToCustomerAddress;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConvertQuoteAddressToCustomerAddressTest extends TestCase
{
    /**
     * @var ConvertQuoteAddressToCustomerAddress
     */
    private $model;

    /**
     * @var MockObject
     */
    private $customerDataMock;

    protected function setUp(): void
    {
        $this->customerDataMock = $this->createMock(Data::class);
        $this->model = new ConvertQuoteAddressToCustomerAddress($this->customerDataMock);
    }

    public function testAfterExportCustomerAddress()
    {
        $attribute = 'attribute';
        $attributeValue = 'attributeValue';
        $quoteAddressMock = $this->createMock(Address::class);
        $customerAddressMock = $this->getMockForAbstractClass(AddressInterface::class);

        $this->customerDataMock->expects($this->once())
            ->method('getCustomerAddressUserDefinedAttributeCodes')
            ->willReturn([$attribute]);

        $quoteAddressMock->expects($this->once())->method('getData')->with($attribute)->willReturn($attributeValue);
        $customerAddressMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with($attribute, $attributeValue)
            ->willReturnSelf();

        $this->assertEquals(
            $customerAddressMock,
            $this->model->afterExportCustomerAddress($quoteAddressMock, $customerAddressMock)
        );
    }
}
