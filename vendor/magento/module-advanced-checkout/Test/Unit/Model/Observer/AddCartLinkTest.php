<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\Observer\AddCartLink;
use Magento\Checkout\Block\Cart\Sidebar;
use Magento\Checkout\Block\Cart\Totals;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddCartLinkTest extends TestCase
{
    /**
     * @var AddCartLink
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $cartMock;

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
        $this->cartMock = $this->createMock(Cart::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->createMock(Event::class);

        $this->model = new AddCartLink($this->cartMock);
    }

    public function testExecuteWhenBlockIsNotSidebar()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $blockMock = $this->getMockBuilder(Totals::class)
            ->addMethods(['setAllowCartLink', 'setCartEmptyMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventMock->expects($this->once())->method('getBlock')->willReturn($blockMock);
        $this->cartMock->expects($this->never())->method('getFailedItems');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWhenFailedItemsCountIsZero()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $blockMock = $this->getMockBuilder(Sidebar::class)
            ->addMethods(['setAllowCartLink', 'setCartEmptyMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventMock->expects($this->once())->method('getBlock')->willReturn($blockMock);
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn([]);
        $blockMock->expects($this->never())->method('setAllowCartLink');

        $this->model->execute($this->observerMock);
    }

    public function testExecute()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $blockMock = $this->getMockBuilder(Sidebar::class)
            ->addMethods(['setAllowCartLink', 'setCartEmptyMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventMock->expects($this->once())->method('getBlock')->willReturn($blockMock);
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn(['one', 'two']);
        $blockMock->expects($this->once())->method('setAllowCartLink')->with(true);
        $blockMock->expects($this->once())->method('setCartEmptyMessage')->with('2 item(s) need your attention.');

        $this->model->execute($this->observerMock);
    }
}
