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
use Magento\Invitation\Model\Invitation;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Observer\InvitationToCustomer;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvitationToCustomerTest extends TestCase
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
     * @var InvitationToCustomer
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardDataMock = $this->createPartialMock(Data::class, ['isEnabledOnFront']);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);

        $this->subject = $objectManager->getObject(
            InvitationToCustomer::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock
            ]
        );
    }

    public function testUpdateRewardsIfRewardsDisabledOnFront()
    {
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $invitationMock = $this->createPartialMock(
            Invitation::class,
            ['getStoreId']
        );

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInvitation'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInvitation')->willReturn($invitationMock);
        $invitationMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

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

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsIfCustomerIdNotSet()
    {
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $invitationMock = $this->getMockBuilder(Invitation::class)
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInvitation'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInvitation')->willReturn($invitationMock);
        $invitationMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

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

        $invitationMock->expects($this->once())->method('getCustomerId')->willReturn(null);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsIfReferralIdNotSet()
    {
        $customerId = 100;
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $invitationMock = $this->getMockBuilder(Invitation::class)
            ->addMethods(['getCustomerId', 'getReferralId'])
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInvitation'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInvitation')->willReturn($invitationMock);
        $invitationMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

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

        $invitationMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $invitationMock->expects($this->once())->method('getReferralId')->willReturn(null);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsSuccess()
    {
        $customerId = 100;
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $invitationMock = $this->getMockBuilder(Invitation::class)
            ->addMethods(['getCustomerId', 'getReferralId'])
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInvitation'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInvitation')->willReturn($invitationMock);
        $invitationMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

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

        $invitationMock->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $invitationMock->expects($this->once())->method('getReferralId')->willReturn(200);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setWebsiteId', 'setAction'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(Reward::REWARD_ACTION_INVITATION_CUSTOMER)->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setActionEntity')
            ->with($invitationMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints')->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
