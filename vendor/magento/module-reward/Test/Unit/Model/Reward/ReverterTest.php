<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Reward;

use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\Reverter;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReverterTest extends TestCase
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
     * @var MockObject
     */
    protected $rewardResourceFactoryMock;

    /**
     * @var Reverter
     */
    protected $model;

    /**
     * @var RewardPointCounter|MockObject
     */
    private $rewardPointCounterMock;

    protected function setUp(): void
    {
        $this->rewardFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardResourceFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\ResourceModel\RewardFactory::class,
            ['create']
        );
        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Reverter(
            $this->storeManagerMock,
            $this->rewardFactoryMock,
            $this->rewardResourceFactoryMock,
            $this->rewardPointCounterMock
        );
    }

    public function testRevertRewardPointsForOrderPositive()
    {
        $customerId = 1;
        $storeId = 2;
        $websiteId = 100;

        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardPointsBalance'])
            ->onlyMethods(['getCustomerId', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->createPartialMock(Store::class, ['getWebsiteId']);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setWebsiteId', 'setPointsDelta', 'setAction'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setPointsDelta')->with(500)->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(Reward::REWARD_ACTION_REVERT)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints')->willReturnSelf();

        $orderMock->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->willReturn(500);

        $this->assertEquals($this->model, $this->model->revertRewardPointsForOrder($orderMock));
    }

    public function testRevertRewardPointsIfNoCustomerId()
    {
        $orderMock = $this->createPartialMock(Order::class, ['getCustomerId']);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn(null);
        $this->assertEquals($this->model, $this->model->revertRewardPointsForOrder($orderMock));
    }

    public function testRevertEarnedPointsForOrder()
    {
        $appliedRuleIds = '1,2,1,1,3,4,3';
        $pointsDelta = -30;
        $customerId = 42;
        $storeId = 1;
        $websiteId = 1;

        $orderMock = $this->createMock(Order::class);
        $storeMock = $this->createMock(Store::class);
        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setWebsiteId', 'setPointsDelta', 'setAction'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($appliedRuleIds);
        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                    1 => '2',
                    4 => '3',
                    5 => '4',
                ]
            )
            ->willReturn(-$pointsDelta);

        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(false);

        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->with($storeId)->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setPointsDelta')->with($pointsDelta)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setAction')->with(Reward::REWARD_ACTION_REVERT)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints');

        $this->assertEquals($this->model, $this->model->revertEarnedRewardPointsForOrder($orderMock));
    }
}
