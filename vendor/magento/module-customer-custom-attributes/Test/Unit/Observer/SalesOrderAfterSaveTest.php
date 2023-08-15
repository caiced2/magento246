<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Model\Sales\Order;
use Magento\CustomerCustomAttributes\Model\Sales\OrderFactory;
use Magento\CustomerCustomAttributes\Observer\SalesOrderAfterSave;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesOrderAfterSaveTest extends TestCase
{
    /**
     * @var SalesOrderAfterSave
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

        $this->observer = new SalesOrderAfterSave($this->orderFactory);
    }

    public function testSalesOrderAfterSave()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrder')->willReturn($dataModel);
        $order->expects($this->once())->method('saveAttributeData')->with($dataModel)->willReturnSelf();
        $this->orderFactory->expects($this->once())->method('create')->willReturn($order);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesOrderAfterSave::class,
            $this->observer->execute($observer)
        );
    }
}
