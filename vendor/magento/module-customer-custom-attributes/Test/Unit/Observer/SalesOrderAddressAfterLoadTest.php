<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Model\Sales\Order\Address;
use Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory;
use Magento\CustomerCustomAttributes\Observer\SalesOrderAddressAfterLoad;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrderAddressAfterLoadTest extends TestCase
{
    /**
     * @var SalesOrderAddressAfterLoad
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $orderAddressFactory;

    protected function setUp(): void
    {
        $this->orderAddressFactory = $this->getMockBuilder(
            AddressFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->observer = new SalesOrderAddressAfterLoad(
            $this->orderAddressFactory
        );
    }

    public function testSalesOrderAddressAfterLoad()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $orderAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getAddress')->willReturn($dataModel);
        $orderAddress->expects($this->once())
            ->method('attachDataToEntities')
            ->with([$dataModel])->willReturnSelf();
        $this->orderAddressFactory->expects($this->once())->method('create')->willReturn($orderAddress);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesOrderAddressAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
