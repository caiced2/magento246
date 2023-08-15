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
use Magento\Reward\Observer\PrepareCustomerOrphanPoints;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrapareCustomerOrphanPointsTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var PrepareCustomerOrphanPoints
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);
        $this->subject = $objectManager->getObject(
            PrepareCustomerOrphanPoints::class,
            ['rewardFactory' => $this->rewardFactoryMock]
        );
    }

    public function testPrepareOrphanPoints()
    {
        $observerMock = $this->createMock(Observer::class);
        $websiteMock = $this->createMock(Website::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getWebsite'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getWebsite')->willReturn($websiteMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $rewardMock = $this->createMock(Reward::class);
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $websiteMock->expects($this->once())->method('getId')->willReturn(1);
        $websiteMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('currencyCode');

        $rewardMock->expects($this->once())
            ->method('prepareOrphanPoints')
            ->with(1, 'currencyCode')->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
