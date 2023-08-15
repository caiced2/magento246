<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory;
use Magento\CustomerCustomAttributes\Model\Sales\Quote\Address;
use Magento\CustomerCustomAttributes\Observer\SalesOrderAddressCollectionAfterLoad;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrderAddressCollectionAfterLoadTest extends TestCase
{
    /**
     * @var SalesOrderAddressCollectionAfterLoad
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

        $this->observer = new SalesOrderAddressCollectionAfterLoad(
            $this->orderAddressFactory
        );
    }

    public function testSalesOrderAddressCollectionAfterLoad()
    {
        $items = ['test', 'data'];
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getOrderAddressCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractDb::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $orderAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getItems')->willReturn($items);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrderAddressCollection')->willReturn($dataModel);
        $orderAddress->expects($this->once())->method('attachDataToEntities')->with($items)->willReturnSelf();
        $this->orderAddressFactory->expects($this->once())->method('create')->willReturn($orderAddress);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesOrderAddressCollectionAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
