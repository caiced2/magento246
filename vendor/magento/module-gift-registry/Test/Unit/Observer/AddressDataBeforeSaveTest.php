<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Observer\AddressDataBeforeSave;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressDataBeforeSaveTest extends TestCase
{
    /**
     * GiftRegistry observer
     *
     * @var AddressDataBeforeSave
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $helperMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);

        $this->model = new AddressDataBeforeSave($this->helperMock);
    }

    /**
     *
     * @dataProvider addressDataBeforeSaveDataProvider
     * @param string $addressId
     * @param int $expectedCalls
     * @param int $expectedResult
     */
    public function testAddressDataBeforeSave($addressId, $expectedCalls, $expectedResult)
    {
        $addressMock = $this->getMockBuilder(Address::class)
            ->addMethods(['setGiftregistryItemId'])
            ->onlyMethods(['getCustomerAddressId'])
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())->method('getCustomerAddressId')->willReturn($addressId);
        $addressMock->expects($this->exactly($expectedCalls))->method('setGiftregistryItemId')->with($expectedResult);

        $event = new DataObject();
        $event->setDataObject($addressMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($event);

        $this->helperMock->expects($this->any())->method('getAddressIdPrefix')->willReturn('gr_address_');

        $this->model->execute($observerMock);
    }

    /**
     * @return array
     */
    public function addressDataBeforeSaveDataProvider()
    {
        return [
            [
                'addressId' => 'gr_address_2',
                'expectedCalls' => 1,
                'expectedResult' => 2,
            ],
            [
                'addressId' => 'gr_address_',
                'expectedCalls' => 0,
                'expectedResult' => ''
            ],
            [
                'addressId' => '2',
                'expectedCalls' => 0,
                'expectedResult' => ''
            ],
        ];
    }
}
