<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\NewRma\Tab\Items\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order\Grid as OrderGrid;
use Magento\Rma\Model\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridTest extends TestCase
{
    /**
     * @var \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid
     */
    protected $grid;

    /**
     * @var Item|MockObject
     */
    protected $rmaItemMock;

    /**
     * @var \Magento\Sales\Model\Order\Item|MockObject
     */
    protected $salesItemMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->rmaItemMock = $this->createPartialMock(Item::class, ['getReturnableQty']);
        $this->salesItemMock = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $this->grid = $objectManager->getObject(
            OrderGrid::class,
            ['rmaItem' => $this->rmaItemMock]
        );
    }

    /**
     *  test method getRemainingQty
     */
    public function testGetRemainingQty()
    {
        $this->rmaItemMock->expects($this->once())
            ->method('getReturnableQty')
            ->willReturn(100.50);

        $this->assertEquals(100.50, $this->grid->getRemainingQty($this->salesItemMock));
    }
}
