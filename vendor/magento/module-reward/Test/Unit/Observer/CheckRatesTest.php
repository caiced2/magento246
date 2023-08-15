<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward\Rate;
use Magento\Reward\Model\Reward\RateFactory;
use Magento\Reward\Observer\CheckRates;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckRatesTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rateFactoryMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $rewardDataMock;

    /**
     * @var CheckRates
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rateFactoryMock = $this->createPartialMock(RateFactory::class, ['create']);
        $this->rewardDataMock = $this->createMock(Data::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->subject = $objectManager->getObject(
            CheckRates::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rateFactory' => $this->rateFactoryMock
            ]
        );
    }

    public function testCheckRatesIfRewardsEnabled()
    {
        $groupId = 1;
        $websiteId = 2;

        $storeMock = $this->createPartialMock(Store::class, ['getWebsiteId']);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);
        $this->rewardDataMock->expects($this->once())->method('setHasRates')->with(true)->willReturnSelf();

        $customerSession = $this->createMock(Session::class);
        $customerSession->expects($this->once())->method('getCustomerGroupId')->willReturn($groupId);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCustomerSession'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getCustomerSession')->willReturn($customerSession);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $rateMock = $this->createPartialMock(
            Rate::class,
            ['fetch', 'getId', 'reset']
        );
        $this->rateFactoryMock->expects($this->once())->method('create')->willReturn($rateMock);

        $valueMap = [
            [$groupId, $websiteId, Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY, $rateMock],
            [$groupId, $websiteId, Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS, $rateMock],
        ];

        $rateMock->expects($this->exactly(2))->method('fetch')->willReturnMap($valueMap);
        $rateMock->expects($this->once())->method('reset')->willReturnSelf();
        $rateMock->expects($this->exactly(2))->method('getId')->willReturn(1);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testCheckRatesIfRewardsDisabled()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(false);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
