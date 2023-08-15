<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Reward\Observer\QuoteMergeAfter;
use PHPUnit\Framework\TestCase;

class QuoteMergeAfterTest extends TestCase
{
    /**
     * @var QuoteMergeAfter
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(QuoteMergeAfter::class);
    }

    public function testSetFlagToResetRewardPoints()
    {
        $observerMock = $this->createMock(Observer::class);
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['setUseRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('setUseRewardPoints')
            ->with(true)->willReturnSelf();

        $sourceMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getUseRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $sourceMock->expects($this->exactly(2))->method('getUseRewardPoints')->willReturn(true);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getQuote', 'getSource'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $eventMock->expects($this->once())->method('getSource')->willReturn($sourceMock);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetFlagToResetRewardPointsIfRewardPointsIsNull()
    {
        $observerMock = $this->createMock(Observer::class);
        $quoteMock = $this->createMock(Quote::class);

        $sourceMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getUseRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $sourceMock->expects($this->once())->method('getUseRewardPoints')->willReturn(false);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getQuote', 'getSource'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $eventMock->expects($this->once())->method('getSource')->willReturn($sourceMock);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
