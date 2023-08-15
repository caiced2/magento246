<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Cron\ScheduledPointsExpiration;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\ResourceModel\Reward\History;
use Magento\Reward\Model\ResourceModel\Reward\HistoryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScheduledPointsExpirationTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardDataMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $historyItemFactoryMock;

    /**
     * @var ScheduledPointsExpiration
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardDataMock = $this->createPartialMock(
            Data::class,
            ['isEnabled', 'isEnabledOnFront', 'getGeneralConfig']
        );
        $this->historyItemFactoryMock = $this->createPartialMock(
            HistoryFactory::class,
            ['create']
        );

        $this->subject = $objectManager->getObject(
            ScheduledPointsExpiration::class,
            [
                'storeManager' => $this->storeManagerMock,
                '_historyItemFactory' => $this->historyItemFactoryMock,
                'rewardData' => $this->rewardDataMock
            ]
        );
    }

    public function testMakePointsExpiredIfRewardsDisabled()
    {
        $this->rewardDataMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->assertEquals($this->subject, $this->subject->execute());
    }

    public function testMakePointsExpiredIfRewardsDisabledOnFront()
    {
        $websiteId = 1;

        $this->rewardDataMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(false);

        $websiteMock = $this->createMock(Website::class);
        $websiteMock->expects($this->once())->method('getId')->willReturn($websiteId);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$websiteMock]);

        $this->assertEquals($this->subject, $this->subject->execute());
    }

    public function testMakePointsExpiredSuccess()
    {
        $websiteId = 1;
        $expireType = 'expire_type';

        $this->rewardDataMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $websiteMock = $this->createMock(Website::class);
        $websiteMock->expects($this->exactly(3))->method('getId')->willReturn($websiteId);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$websiteMock]);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $this->rewardDataMock->expects($this->once())
            ->method('getGeneralConfig')
            ->with('expiry_calculation', $websiteId)
            ->willReturn($expireType);

        $rewardHistoryMock = $this->createMock(History::class);
        $this->historyItemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rewardHistoryMock);

        $rewardHistoryMock->expects($this->once())
            ->method('expirePoints')
            ->with($websiteId, $expireType, 100)->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute());
    }
}
