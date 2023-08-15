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
use Magento\GiftWrapping\Observer\SalesEventOrderItemToQuoteItem;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesEventOrderItemToQuoteItemTest extends TestCase
{
    /** @var SalesEventOrderItemToQuoteItem */
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
            ->addMethods(['getOrderItem', 'getQuoteItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_model = $objectManagerHelper->getObject(
            SalesEventOrderItemToQuoteItem::class,
            [
                'giftWrappingData' => $this->helperDataMock
            ]
        );
        $this->_event = new DataObject();
        $this->_observer = new Observer(['event' => $this->_event]);
    }

    public function testSalesEventOrderItemToQuoteItemWithReorderedOrder()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getReordered'])
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemMock = $this->createPartialMock(Item::class, ['getOrder']);
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getOrderItem')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getReordered')->willReturn(true);

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderItemToQuoteItemWithGiftWrappingThatNotAllowedForItems()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getReordered'])
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemMock = $this->createPartialMock(Item::class, ['getOrder']);
        $storeMock = $this->createMock(Store::class);

        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getOrderItem')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getReordered')->willReturn(false);

        $storeId = 12;
        $orderMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForItems')
            ->with($storeId)
            ->willReturn(null);

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderItemToQuoteItem()
    {
        $orderItemMock = $this->createPartialMock(
            Item::class,
            [
                'getOrder',
                'getGwId',
                'getGwBasePrice',
                'getGwPrice',
                'getGwBaseTaxAmount',
                'getGwTaxAmount',
                '__wakeup'
            ]
        );
        $quoteItemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)->addMethods(
            ['setGwId', 'setGwBasePrice', 'setGwPrice', 'setGwBaseTaxAmount', 'setGwTaxAmount']
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getOrderItem')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getOrder')->willReturn(null);
        $this->eventMock->expects($this->once())->method('getQuoteItem')->willReturn($quoteItemMock);
        $orderItemMock->expects($this->once())->method('getGwId')->willReturn(11);
        $orderItemMock->expects($this->once())->method('getGwBasePrice')->willReturn(22);
        $orderItemMock->expects($this->once())->method('getGwPrice')->willReturn(33);
        $orderItemMock->expects($this->once())->method('getGwBaseTaxAmount')->willReturn(44);
        $orderItemMock->expects($this->once())->method('getGwTaxAmount')->willReturn(55);
        $quoteItemMock->expects($this->once())
            ->method('setGwId')
            ->with(11)
            ->willReturn($quoteItemMock);
        $quoteItemMock->expects($this->once())
            ->method('setGwBasePrice')
            ->with(22)
            ->willReturn($quoteItemMock);
        $quoteItemMock->expects($this->once())
            ->method('setGwPrice')
            ->with(33)
            ->willReturn($quoteItemMock);
        $quoteItemMock->expects($this->once())
            ->method('setGwBaseTaxAmount')
            ->with(44)->willReturn($quoteItemMock);
        $quoteItemMock->expects($this->once())
            ->method('setGwTaxAmount')
            ->with(55)
            ->willReturn($quoteItemMock);

        $this->_model->execute($this->observerMock);
    }
}
