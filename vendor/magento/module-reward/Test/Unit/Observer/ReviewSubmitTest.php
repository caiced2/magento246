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
use Magento\Review\Model\Review;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Observer\ReviewSubmit;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReviewSubmitTest extends TestCase
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
    protected $rewardDataMock;

    /**
     * @var ReviewSubmit
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);
        $this->rewardDataMock = $this->createMock(Data::class);

        $this->subject = $objectManager->getObject(
            ReviewSubmit::class,
            [
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock,
                'rewardData' => $this->rewardDataMock
            ]
        );
    }

    /**
     * Test update reward points when reward is disable in front
     */
    public function testUpdateRewardPointsWhenRewardDisabledInFront(): void
    {
        $websiteId = 2;

        $observerMock = $this->createMock(Observer::class);
        $reviewMock = $this->createMock(Review::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getObject')->willReturn($reviewMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(false);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    /**
     * Test update reward points when review is not approved
     */
    public function testUpdateRewardPointsIfReviewNotApproved(): void
    {
        $websiteId = 2;

        $observerMock = $this->createMock(Observer::class);
        $reviewMock = $this->createMock(Review::class);
        $reviewMock->expects($this->once())->method('isApproved')->willReturn(false);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getObject')->willReturn($reviewMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    /**
     * Test update reward points when customer id is not set
     */
    public function testUpdateRewardPointsIfCustomerIdNotSet(): void
    {
        $websiteId = 2;

        $observerMock = $this->createMock(Observer::class);
        $reviewMock = $this->getMockBuilder(Review::class)
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['isApproved'])
            ->disableOriginalConstructor()
            ->getMock();
        $reviewMock->expects($this->once())->method('isApproved')->willReturn(true);
        $reviewMock->expects($this->once())->method('getCustomerId')->willReturn(null);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getObject')->willReturn($reviewMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    /**
     * Test update reward points when store is not set
     */
    public function testUpdateRewardPointsIfStoreIsNotSet(): void
    {
        $storeId = null;
        $websiteId = 1;

        $observerMock = $this->createMock(Observer::class);
        $reviewMock = $this->getMockBuilder(Review::class)
            ->addMethods(['getStoreId'])
            ->onlyMethods(['isApproved'])
            ->disableOriginalConstructor()
            ->getMock();
        $reviewMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $reviewMock->expects($this->once())->method('isApproved')->willReturn(true);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getObject')->willReturn($reviewMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    /**
     * Test reward points with valid data
     */
    public function testUpdateRewardPoints(): void
    {
        $storeId = 1;
        $websiteId = 2;
        $customerId = 100;

        $observerMock = $this->createMock(Observer::class);
        $reviewMock = $this->getMockBuilder(Review::class)
            ->addMethods(['getCustomerId', 'getStoreId'])
            ->onlyMethods(['isApproved'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getObject')->willReturn($reviewMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $reviewMock->expects($this->once())->method('isApproved')->willReturn(true);
        $reviewMock->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $reviewMock->expects($this->exactly(1))->method('getStoreId')->willReturn($storeId);

        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setStore', 'setAction'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setStore')->with($storeId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setActionEntity')->with($reviewMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setAction')
            ->with(Reward::REWARD_ACTION_REVIEW)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints')->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
