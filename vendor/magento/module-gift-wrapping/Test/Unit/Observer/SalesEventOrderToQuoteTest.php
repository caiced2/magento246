<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Observer\SalesEventOrderToQuote;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesEventOrderToQuoteTest extends TestCase
{
    /** @var SalesEventOrderToQuote */
    protected $_model;

    /**
     * @var Observer
     */
    protected $_observer;

    /**
     * @var DataObject
     */
    protected $_event;

    /**
     * @var MockObject
     */
    protected $helperDataMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $eventMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->helperDataMock = $this->createMock(Data::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_model = $objectManagerHelper->getObject(
            SalesEventOrderToQuote::class,
            [
                'giftWrappingData' =>  $this->helperDataMock
            ]
        );
        $this->_event = new DataObject();
        $this->_observer = new Observer(['event' => $this->_event]);
    }

    public function testSalesEventOrderToQuoteForReorderedOrder()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getReordered'])
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock = $this->createMock(Store::class);
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeId = 12;
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $orderMock->expects($this->once())->method('getReordered')->willReturn(true);

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderToQuoteWithGiftWrappingThatNotAvailableForOrder()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getReordered'])
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock = $this->createMock(Store::class);
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeId = 12;
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $orderMock->expects($this->once())->method('getReordered')->willReturn(false);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')
            ->with($storeId)
            ->willReturn(false);

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderToQuote()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getReordered', 'getGwId', 'getGwAllowGiftReceipt', 'getGwAddCard'])
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock = $this->createMock(Store::class);
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['setGwId', 'setGwAllowGiftReceipt', 'setGwAddCard'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeId = 12;
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $orderMock->expects($this->once())->method('getReordered')->willReturn(false);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')
            ->with($storeId)
            ->willReturn(true);
        $this->eventMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $orderMock->expects($this->once())->method('getGwId')->willReturn(1);
        $orderMock->expects($this->once())
            ->method('getGwAllowGiftReceipt')->willReturn('Gift_recipient');
        $orderMock->expects($this->once())->method('getGwAddCard')->willReturn('add_cart');
        $quoteMock->expects($this->once())->method('setGwId')->with(1)->willReturn($quoteMock);
        $quoteMock->expects($this->once())
            ->method('setGwAllowGiftReceipt')->with('Gift_recipient')->willReturn($quoteMock);
        $quoteMock->expects($this->once())
            ->method('setGwAddCard')->with('add_cart')->willReturn($quoteMock);

        $this->_model->execute($this->observerMock);
    }
}
