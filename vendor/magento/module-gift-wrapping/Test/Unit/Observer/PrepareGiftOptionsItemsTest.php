<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Observer\PrepareGiftOptionsItems;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrepareGiftOptionsItemsTest extends TestCase
{
    /** @var PrepareGiftOptionsItems */
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
            ->addMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_model = $objectManagerHelper->getObject(
            PrepareGiftOptionsItems::class,
            [
                'giftWrappingData' => $this->helperDataMock
            ]
        );
        $this->_event = new DataObject();
        $this->_observer = new Observer(['event' => $this->_event]);
    }

    public function testPrepareGiftOptionsItems()
    {
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getProduct', 'getIsVirtual', 'setIsGiftOptionsAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftWrappingAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getItems')->willReturn([$itemMock]);
        $itemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('getGiftWrappingAvailable')->willReturn(true);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForProduct')->with(true)->willReturn(true);
        $itemMock->expects($this->once())->method('getIsVirtual')->willReturn(false);
        $itemMock->expects($this->once())->method('setIsGiftOptionsAvailable')->with(true);

        $this->_model->execute($this->observerMock);
    }

    public function testPrepareGiftOptionsItemsWithVirtualProduct()
    {
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getProduct', 'getIsVirtual', 'setIsGiftOptionsAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftWrappingAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getItems')->willReturn([$itemMock]);
        $itemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('getGiftWrappingAvailable')->willReturn(true);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForProduct')->with(true)->willReturn(true);
        $itemMock->expects($this->once())->method('getIsVirtual')->willReturn(true);
        $itemMock->expects($this->never())->method('setIsGiftOptionsAvailable');

        $this->_model->execute($this->observerMock);
    }

    public function testPrepareGiftOptionsItemsWithNotAllowedProduct()
    {
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getProduct', 'getIsVirtual', 'setIsGiftOptionsAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftWrappingAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getItems')->willReturn([$itemMock]);
        $itemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('getGiftWrappingAvailable')->willReturn(false);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForProduct')->with(false)->willReturn(false);
        $itemMock->expects($this->never())->method('getIsVirtual');
        $itemMock->expects($this->never())->method('setIsGiftOptionsAvailable');

        $this->_model->execute($this->observerMock);
    }
}
