<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Observer\AddressDataBeforeLoad;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressDataBeforeLoadTest extends TestCase
{
    /**
     * @var AddressDataBeforeLoad
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $giftRegistryDataMock;

    protected function setUp(): void
    {
        $this->giftRegistryDataMock = $this->createMock(Data::class);
        $this->model = new AddressDataBeforeLoad($this->giftRegistryDataMock);
    }

    public function testexecute()
    {
        $addressId = 'prefixId';
        $prefix = 'prefix';
        $dataObject = $this->getMockBuilder(DataObject::class)
            ->addMethods(['setGiftregistryItemId', 'setCustomerAddressId'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject->expects($this->once())->method('setGiftregistryItemId')->with('Id')->willReturnSelf();
        $dataObject->expects($this->once())->method('setCustomerAddressId')->with($addressId)->willReturnSelf();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getValue', 'getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getValue')->willReturn($addressId);
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($dataObject);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->giftRegistryDataMock->expects($this->once())->method('getAddressIdPrefix')->willReturn($prefix);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
