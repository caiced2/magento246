<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Observer\PrepareGiftOptions;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for \Magento\GiftWrapping\Observer\PrepareGiftOptions observer.
 */
class PrepareGiftOptionsTest extends TestCase
{
    /** @var PrepareGiftOptions */
    private $model;

    /**
     * @var Data|MockObject
     */
    private $helperDataMock;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var Address|MockObject
     */
    private $entityMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->helperDataMock = $this->createMock(Data::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getEntity'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getQuote', 'setIsGiftOptionsAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->createPartialMock(
            Quote::class,
            [
                'getIsVirtual',
                '__wakeup'
            ]
        );
        $this->model = $objectManagerHelper->getObject(
            PrepareGiftOptions::class,
            [
                'giftWrappingData' => $this->helperDataMock
            ]
        );
    }

    /**
     * Test the most expected case when we need to enable gift wrapping.
     *
     * @return void
     */
    public function testPrepareGiftOptions()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getEntity')->willReturn($this->entityMock);
        $this->entityMock->expects($this->exactly(2))->method('getQuote')->willReturn($this->quoteMock);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')->willReturn(true);
        $this->quoteMock->expects($this->once())->method('getIsVirtual')->willReturn(false);
        $this->entityMock->expects($this->once())->method('setIsGiftOptionsAvailable')->with(true);

        $this->model->execute($this->observerMock);
    }

    /**
     * Test with virtual quote and enabled gift wrapping setting.
     * In this case we don't need to enable gift wrapping option for frontend.
     *
     * @return void
     */
    public function testPrepareGiftOptionsWithVirtualQuote()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getEntity')->willReturn($this->entityMock);
        $this->entityMock->expects($this->exactly(2))->method('getQuote')->willReturn($this->quoteMock);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')->willReturn(true);
        $this->quoteMock->expects($this->once())->method('getIsVirtual')->willReturn(true);
        $this->entityMock->expects($this->never())->method('setIsGiftOptionsAvailable')->with(true);

        $this->model->execute($this->observerMock);
    }

    /**
     * Test with disabled gift wrapping setting.
     *
     * @return void
     */
    public function testPrepareGiftOptionsWithGiftWrappingSettingDisabled()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getEntity')->willReturn($this->entityMock);
        $this->entityMock->expects($this->never())->method('getQuote')->willReturn($this->quoteMock);
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')->willReturn(false);
        $this->quoteMock->expects($this->never())->method('getIsVirtual')->willReturn(true);
        $this->entityMock->expects($this->never())->method('setIsGiftOptionsAvailable')->with(true);

        $this->model->execute($this->observerMock);
    }
}
