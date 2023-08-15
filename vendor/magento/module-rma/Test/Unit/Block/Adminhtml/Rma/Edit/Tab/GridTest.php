<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Tab;

use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid;
use Magento\Rma\Model\Item;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridTest extends TestCase
{
    /**
     * @var Grid
     */
    protected $grid;

    /**
     * @var Item|MockObject
     */
    protected $itemMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var Order|MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Model\Order\Item|MockObject
     */
    protected $orderItemMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->itemMock = $this->createPartialMock(Item::class, ['getReturnableQty']);
        $this->registryMock = $this->createMock(Registry::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->orderItemMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Item::class,
            ['getId', 'getQtyShipped', 'getQtyReturned']
        );
        $this->registryMock->expects($this->exactly(2))
            ->method('registry')
            ->with('current_order')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock->expects($this->once())
            ->method('getId')
            ->willReturn(15);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(1050);
        $this->orderItemMock->expects($this->once())
            ->method('getQtyReturned')
            ->willReturn(100500);
        $this->grid = $objectManager->getObject(
            Grid::class,
            [
                'coreRegistry' => $this->registryMock
            ]
        );
    }

    /**
     *  test method getRemainingQty
     */
    public function testGetRemainingQty()
    {
        $this->itemMock->expects($this->once())
            ->method('getReturnableQty')
            ->willReturn(100.50);
        $this->assertEquals(100.50, $this->grid->getRemainingQty($this->itemMock));
    }

    /**
     * test protected method _gatherOrderItemsData
     */
    public function testGatherOrderItemsData()
    {
        $expected = [15 => ['qty_shipped' => 1050, 'qty_returned' => 100500]];
        $this->assertEquals($expected, $this->grid->getOrderItemsData());
    }
}
