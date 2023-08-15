<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Order\View\Tab;

use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Order\View\Tab\Rma;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Order RMA tab test
 */
class RmaTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Rma
     */
    protected $rmaTab;

    /**
     * @var MockObject
     */
    protected $registryMock;

    /**
     * @var Order|MockObject
     */
    protected $orderMock;

    /**
     * @var Item|MockObject
     */
    protected $orderItemMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->createMock(Order::class);
        $this->orderItemMock = $this->createPartialMock(
            Item::class,
            ['getQtyReturned', 'getQtyShipped']
        );
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->with('current_order')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->any())
            ->method('getItemsCollection')
            ->willReturn([$this->orderItemMock]);

        $this->rmaTab = $this->objectManager->getObject(
            Rma::class,
            [
                'coreRegistry' => $this->registryMock
            ]
        );
    }

    public function testCanShowTabWhenProductShipped()
    {
        $expectedResult = true;
        $this->orderItemMock->expects($this->any())
            ->method('getQtyShipped')
            ->willReturn(1);
        $this->orderItemMock->expects($this->any())
            ->method('getQtyReturned')
            ->willReturn(0);
        $this->assertEquals($expectedResult, $this->rmaTab->canShowTab());
    }

    public function testCanShowTabWhenProductReturned()
    {
        $expectedResult = true;
        $this->orderItemMock->expects($this->any())
            ->method('getQtyShipped')
            ->willReturn(0);
        $this->orderItemMock->expects($this->any())
            ->method('getQtyReturned')
            ->willReturn(1);
        $this->assertEquals($expectedResult, $this->rmaTab->canShowTab());
    }

    public function testCanNotShowTabWhenProductNotShipped()
    {
        $expectedResult = false;
        $this->orderItemMock->expects($this->any())
            ->method('getQtyShipped')
            ->willReturn(0);
        $this->orderItemMock->expects($this->any())
            ->method('getQtyReturned')
            ->willReturn(0);
        $this->assertEquals($expectedResult, $this->rmaTab->canShowTab());
    }
}
