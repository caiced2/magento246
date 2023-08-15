<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Model\Sales\Order;
use Magento\CustomerCustomAttributes\Model\Sales\OrderFactory;
use Magento\CustomerCustomAttributes\Observer\SalesOrderAfterLoad;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrderAfterLoadTest extends TestCase
{
    /**
     * @var SalesOrderAfterLoad
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $orderFactory;

    protected function setUp(): void
    {
        $this->orderFactory = $this->getMockBuilder(
            OrderFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->observer = new SalesOrderAfterLoad($this->orderFactory);
    }

    public function testSalesOrderAfterLoad()
    {
        $orderId = 1;
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getId')->willReturn($orderId);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrder')->willReturn($dataModel);
        $order->expects($this->once())->method('load')->with($orderId)->willReturnSelf();
        $order->expects($this->once())->method('attachAttributeData')->with($dataModel)->willReturnSelf();
        $this->orderFactory->expects($this->once())->method('create')->willReturn($order);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesOrderAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
