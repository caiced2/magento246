<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Plugin\CustomerRegister;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerRegisterTest extends TestCase
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
     * @var MockObject
     */
    protected $loggerMock;

    /**
     * @var MockObject
     */
    protected $customerRegistryMock;

    /**
     * @var CustomerRegister
     */
    protected $subject;

    /**
     * @var MockObject
     */
    protected $accountManagementMock;

    /**
     * @var MockObject
     */
    private $customerMock;

    /**
     * @var MockObject
     */
    private $storeMock;

    /**
     * @var MockObject
     */
    private $customerModelMock;

    /**
     * @var MockObject
     */
    private $customerResourceMock;

    /**
     * Set up test
     */
    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardDataMock = $this->createMock(
            Data::class
        );
        $this->storeManagerMock = $this->createMock(
            StoreManagerInterface::class
        );
        $this->rewardFactoryMock = $this->createPartialMock(
            RewardFactory::class,
            ['create']
        );
        $this->loggerMock = $this->createMock(
            LoggerInterface::class
        );
        $this->customerRegistryMock = $this->createMock(
            CustomerRegistry::class
        );
        $this->accountManagementMock = $this->createMock(
            AccountManagement::class
        );

        $this->customerMock = $this->createMock(
            Customer::class
        );

        $this->storeMock = $this->createMock(
            Store::class
        );

        $this->customerModelMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)->addMethods(
            ['setRewardUpdateNotification', 'setRewardWarningNotification']
        )
            ->onlyMethods(['getResource'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerResourceMock = $this->createMock(
            \Magento\Customer\Model\ResourceModel\Customer::class
        );

        $this->subject = $objectManager->getObject(
            CustomerRegister::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock,
                'logger' => $this->loggerMock,
                'customerRegistry' => $this->customerRegistryMock
            ]
        );
    }

    public function testUpdateRewardPointsWhenRewardDisabledInFront()
    {
        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->willReturn(false);

        $this->assertEquals(
            $this->customerMock,
            $this->subject->afterCreateAccountWithPasswordHash(
                $this->accountManagementMock,
                $this->customerMock
            )
        );
    }

    public function testUpdateRewardPointsSuccess()
    {
        $notificationConfig = 1;
        $websiteId = 74;
        $customerEmail = 'test@test.tst';

        $this->customerMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($customerEmail);
        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->willReturn(true);
        $this->storeManagerMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->rewardDataMock->expects($this->once())
            ->method('getNotificationConfig')
            ->with('subscribe_by_default', $websiteId)
            ->willReturn($notificationConfig);
        $this->customerRegistryMock->expects($this->once())
            ->method('retrieveByEmail')
            ->with($customerEmail)
            ->willReturn($this->customerModelMock);
        $this->customerModelMock->expects($this->once())
            ->method('setRewardUpdateNotification')
            ->with($notificationConfig);
        $this->customerModelMock->expects($this->once())
            ->method('setRewardWarningNotification')
            ->with($notificationConfig);
        $this->customerModelMock->expects($this->exactly(2))
            ->method('getResource')
            ->willReturn($this->customerResourceMock);

        $this->customerResourceMock->expects($this->exactly(2))
            ->method('saveAttribute')
            ->withConsecutive(
                [$this->customerModelMock, 'reward_update_notification'],
                [$this->customerModelMock, 'reward_warning_notification']
            );

        $this->rewardFactoryTest();

        $this->assertEquals(
            $this->customerMock,
            $this->subject->afterCreateAccountWithPasswordHash(
                $this->accountManagementMock,
                $this->customerMock
            )
        );
    }

    private function rewardFactoryTest()
    {
        $storeId = 42;
        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setStore', 'setAction'])
            ->onlyMethods(['setCustomer', 'setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rewardFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rewardMock);
        $rewardMock->expects($this->once())
            ->method('setCustomer')
            ->with($this->customerMock)
            ->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setActionEntity')
            ->with($this->customerMock)
            ->willReturnSelf();
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $rewardMock->expects($this->once())
            ->method('setStore')
            ->with($storeId)
            ->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(Reward::REWARD_ACTION_REGISTER)
            ->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints');
    }

    public function testUpdateRewardsThrowsException()
    {
        $notificationConfig = 1;
        $websiteId = 74;
        $exception = new \Exception('Something went wrong');

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->willReturn(true);
        $this->storeManagerMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->rewardDataMock->expects($this->once())
            ->method('getNotificationConfig')
            ->with('subscribe_by_default', $websiteId)
            ->willReturn($notificationConfig);

        $this->customerRegistryMock->expects($this->once())
            ->method('retrieveByEmail')
            ->willReturn($this->customerModelMock);
        $this->customerModelMock->expects($this->exactly(2))
            ->method('getResource')
            ->willReturn($this->customerResourceMock);
        $this->rewardFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->assertEquals(
            $this->customerMock,
            $this->subject->afterCreateAccountWithPasswordHash(
                $this->accountManagementMock,
                $this->customerMock
            )
        );
    }
}
