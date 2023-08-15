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
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Observer\ReturnRewardPoints;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReturnRewardPointsTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ReturnRewardPoints
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);
        $this->subject = $objectManager->getObject(
            ReturnRewardPoints::class,
            ['storeManager' => $this->storeManagerMock, 'rewardFactory' => $this->rewardFactoryMock]
        );
    }

    public function testReturnRewardPointsIfPointsBalanceIsZero()
    {
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->willReturn(0);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testReturnRewardPointsIfCustomerDeleted()
    {
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardPointsBalance'])
            ->onlyMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->willReturn(1);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn(null);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testReturnRewardPoints()
    {
        $customerId = 100;
        $storeId = 1;
        $websiteId = 2;
        $pointsBalance = 100;

        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardPointsBalance'])
            ->onlyMethods(['getCustomerId', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->exactly(2))
            ->method('getRewardPointsBalance')
            ->willReturn($pointsBalance);
        $orderMock->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setWebsiteId', 'setAction', 'setPointsDelta'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setPointsDelta')->with($pointsBalance)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints')->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(Reward::REWARD_ACTION_REVERT)->willReturnSelf();

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
