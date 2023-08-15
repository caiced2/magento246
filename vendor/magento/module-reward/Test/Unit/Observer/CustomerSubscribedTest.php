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
use Magento\Newsletter\Model\Subscriber;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Observer\CustomerSubscribed;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerSubscribedTest extends TestCase
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
     * @var CustomerSubscribed
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardDataMock = $this->createMock(Data::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);

        $this->subject = $objectManager->getObject(
            CustomerSubscribed::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock
            ]
        );
    }

    public function testUpdateRewardsAfterSubscribtionIfSubscriberExist()
    {
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $subscriberMock = $this->createPartialMock(
            Subscriber::class,
            ['isObjectNew']
        );

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getSubscriber'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $subscriberMock->expects($this->once())->method('isObjectNew')->willReturn(false);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsAfterSubscribtionIfCustomerNotExist()
    {
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $subscriberMock = $this->getMockBuilder(Subscriber::class)
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getSubscriber'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $subscriberMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $subscriberMock->expects($this->once())->method('getCustomerId')->willReturn(null);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsAfterSubscribtionIfRewardDisabledOnFront()
    {
        $customerId = 10;
        $storeId = 2;
        $websiteId = 1;
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $subscriberMock = $this->getMockBuilder(Subscriber::class)
            ->addMethods(['getCustomerId', 'getStoreId'])
            ->onlyMethods(['isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getSubscriber'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $subscriberMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $subscriberMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(false);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsAfterSubscribtionSuccess()
    {
        $customerId = 10;
        $storeId = 2;
        $websiteId = 1;
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $subscriberMock = $this->getMockBuilder(Subscriber::class)
            ->addMethods(['getCustomerId', 'getStoreId'])
            ->onlyMethods(['isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getSubscriber'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $subscriberMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $subscriberMock->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $subscriberMock->expects($this->exactly(2))->method('getStoreId')->willReturn($storeId);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setStore', 'setAction'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setStore')->with($storeId)->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(Reward::REWARD_ACTION_NEWSLETTER)->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setActionEntity')
            ->with($subscriberMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints')->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
