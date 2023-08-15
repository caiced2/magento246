<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\Sales\Order;
use Magento\CustomerCustomAttributes\Model\Sales\OrderFactory;
use Magento\CustomerCustomAttributes\Model\Sales\Quote;
use Magento\CustomerCustomAttributes\Model\Sales\QuoteFactory;
use Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeDelete;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnterpriseCustomerAttributeDeleteTest extends TestCase
{
    /**
     * @var EnterpriseCustomerAttributeDelete
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $orderFactory;

    /**
     * @var MockObject
     */
    protected $quoteFactory;

    protected function setUp(): void
    {
        $this->quoteFactory = $this->getMockBuilder(
            QuoteFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->orderFactory = $this->getMockBuilder(
            OrderFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->observer = new EnterpriseCustomerAttributeDelete(
            $this->orderFactory,
            $this->quoteFactory
        );
    }

    public function testEnterpriseCustomerAttributeDelete()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(Attribute::class)
            ->setMethods(['isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('isObjectNew')->willReturn(false);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getAttribute')->willReturn($dataModel);
        $quote->expects($this->once())->method('deleteAttribute')->with($dataModel)->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);
        $order->expects($this->once())->method('deleteAttribute')->with($dataModel)->willReturnSelf();
        $this->orderFactory->expects($this->once())->method('create')->willReturn($order);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            EnterpriseCustomerAttributeDelete::class,
            $this->observer->execute($observer)
        );
    }
}
