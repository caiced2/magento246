<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Rma;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Rma\Model\Rma\Create;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @var CustomerFactory|MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var OrderFactory|MockObject
     */
    protected $orderFactoryMock;

    /**
     * @var Customer|MockObject
     */
    protected $customerMock;

    /**
     * @var Create
     */
    protected $rmaModel;

    protected function setUp(): void
    {
        $this->customerFactoryMock = $this->createPartialMock(
            CustomerFactory::class,
            ['create']
        );
        $this->orderFactoryMock = $this->createPartialMock(OrderFactory::class, ['create']);
        $this->customerMock = $this->createPartialMock(Customer::class, ['load']);
        $data = ['order_id' => 1000000013, 'customer_id' => 2];

        $this->rmaModel = new Create($this->customerFactoryMock, $this->orderFactoryMock, $data);

        $this->customerMock->expects($this->once())
            ->method('load')
            ->with($this->rmaModel->getCustomerId())->willReturnSelf();
        $this->customerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->customerMock);
    }

    public function testGetCustomer()
    {
        $this->assertEquals($this->customerMock, $this->rmaModel->getCustomer());
    }

    public function testGetCustomerNoId()
    {
        $this->mockOrder($this->rmaModel->getCustomerId());
        $this->rmaModel->unsetData('customer_id');

        $this->assertEquals($this->customerMock, $this->rmaModel->getCustomer());
    }

    /**
     * Get Order Mock
     *
     * @param int $customerId
     * @return MockObject
     */
    public function mockOrder($customerId)
    {
        $orderMock = $this->createPartialMock(Order::class, ['load', 'getCustomerId']);
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);
        $orderMock->expects($this->once())
            ->method('load')
            ->with($this->rmaModel->getOrderId())->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        return $orderMock;
    }
}
