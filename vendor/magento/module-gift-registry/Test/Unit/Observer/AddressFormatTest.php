<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\GiftRegistry\Observer\AddressFormat;
use PHPUnit\Framework\TestCase;

class AddressFormatTest extends TestCase
{
    /**
     * @var AddressFormat
     */
    protected $model;

    protected function setUp(): void
    {
        $this->model = new AddressFormat();
    }

    public function testFormatIfGiftRegistryItemIdIsNull()
    {
        $format = 'format';
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getType', 'getAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $typeMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getPrevFormat', 'setDefaultFormat'])
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock = $this->getMockBuilder(AbstractAddress::class)
            ->addMethods(['getGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock->expects($this->once())->method('getType')->willReturn($typeMock);
        $eventMock->expects($this->once())->method('getAddress')->willReturn($addressMock);

        $addressMock->expects($this->once())->method('getGiftregistryItemId')->willReturn(null);
        $typeMock->expects($this->exactly(2))->method('getPrevFormat')->willReturn($format);
        $typeMock->expects($this->once())->method('setDefaultFormat')->with($format)->willReturn($format);

        $this->assertEquals($this->model, $this->model->format($observerMock));
    }

    public function testFormat()
    {
        $giftRegistryItemId = 100;
        $format = 'format';
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getType', 'getAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $typeMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getPrevFormat', 'setDefaultFormat', 'getDefaultFormat', 'setPrevFormat'])
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock = $this->getMockBuilder(AbstractAddress::class)
            ->addMethods(['getGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock->expects($this->once())->method('getType')->willReturn($typeMock);
        $eventMock->expects($this->once())->method('getAddress')->willReturn($addressMock);

        $addressMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($giftRegistryItemId);

        $typeMock->expects($this->once())->method('getPrevFormat')->willReturn(null);
        $typeMock->expects($this->once())->method('getDefaultFormat')->willReturn($format);
        $typeMock->expects($this->once())->method('setPrevFormat')->with($format)->willReturnSelf();
        $typeMock->expects($this->once())
            ->method('setDefaultFormat')
            ->with(__("Ship to the recipient's address."))
            ->willReturnSelf();

        $this->assertEquals($this->model, $this->model->format($observerMock));
    }
}
