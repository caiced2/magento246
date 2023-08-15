<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Reward\Reverter;
use Magento\Reward\Observer\RevertRewardPointsForAllOrders;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RevertRewardPointsForAllOrdersTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $reverterMock;

    /**
     * @var RevertRewardPointsForAllOrders
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->reverterMock = $this->createMock(Reverter::class);
        $this->subject = $objectManager->getObject(
            RevertRewardPointsForAllOrders::class,
            ['reverter' => $this->reverterMock]
        );
    }

    public function testRevertRewardPointsIfNoOrders()
    {
        $observerMock = $this->createMock(Observer::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrders'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrders')->willReturn([]);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testRevertRewardPoints()
    {
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->createMock(Order::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrders'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrders')->willReturn([$orderMock]);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->reverterMock->expects($this->once())
            ->method('revertRewardPointsForOrder')
            ->with($orderMock)->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
