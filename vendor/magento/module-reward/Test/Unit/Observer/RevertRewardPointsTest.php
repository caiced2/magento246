<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Reward\Model\Reward\Reverter;
use Magento\Reward\Observer\RevertRewardPoints;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RevertRewardPointsTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $reverterMock;

    /**
     * @var RevertRewardPoints
     */
    protected $model;

    protected function setUp(): void
    {
        $this->reverterMock = $this->createMock(Reverter::class);
        $this->model = new RevertRewardPoints($this->reverterMock);
    }

    public function testRevertRewardPointsIfOrderIsNull()
    {
        $observerMock = $this->createMock(Observer::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn(null);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testRevertRewardPoints()
    {
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->createMock(Order::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->reverterMock->expects($this->once())
            ->method('revertRewardPointsForOrder')
            ->with($orderMock)->willReturnSelf();
        $this->reverterMock->expects($this->never())->method('revertEarnedRewardPointsForOrder')->with($orderMock)
            ->willReturnSelf();

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
