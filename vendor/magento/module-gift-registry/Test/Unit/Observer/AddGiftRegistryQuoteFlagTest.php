<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Observer\AddGiftRegistryQuoteFlag;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddGiftRegistryQuoteFlagTest extends TestCase
{
    /**
     * @var AddGiftRegistryQuoteFlag
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $dataMock;

    protected function setUp(): void
    {
        $this->dataMock = $this->createMock(Data::class);
        $this->model = new AddGiftRegistryQuoteFlag($this->dataMock);
    }

    public function testexecuteIfRegistryDisabled()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->dataMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testexecuteIfRegistryItemIdIsNull()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->dataMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())->method('getGiftregistryItemId')->willReturn(null);

        $quoteItemMock = $this->createMock(Item::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getProduct', 'getQuoteItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $eventMock->expects($this->once())->method('getQuoteItem')->willReturn($quoteItemMock);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testexecute()
    {
        $giftRegistryItemId = 100;
        $observerMock = $this->createMock(Observer::class);
        $this->dataMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($giftRegistryItemId);

        $quoteItemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['setGiftregistryItemId'])
            ->onlyMethods(['getParentItem'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getProduct', 'getQuoteItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $eventMock->expects($this->once())->method('getQuoteItem')->willReturn($quoteItemMock);

        $quoteItemMock->expects($this->once())
            ->method('setGiftregistryItemId')
            ->with($giftRegistryItemId)
            ->willReturnSelf();

        $parentItemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['setGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();
        $parentItemMock->expects($this->once())
            ->method('setGiftregistryItemId')
            ->with($giftRegistryItemId)
            ->willReturnSelf();

        $quoteItemMock->expects($this->once())->method('getParentItem')->willReturn($parentItemMock);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
