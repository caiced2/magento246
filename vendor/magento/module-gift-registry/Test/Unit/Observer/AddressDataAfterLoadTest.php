<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Observer\AddressDataAfterLoad;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressDataAfterLoadTest extends TestCase
{
    /**
     * @var AddressDataAfterLoad
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $giftRegistryDataMock;

    /**
     * @var MockObject
     */
    protected $customerSessionMock;

    /**
     * @var MockObject
     */
    protected $entityFactoryMock;

    protected function setUp(): void
    {
        $this->giftRegistryDataMock = $this->createMock(Data::class);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->entityFactoryMock = $this->createPartialMock(
            EntityFactory::class,
            ['create']
        );

        $this->model = new AddressDataAfterLoad(
            $this->giftRegistryDataMock,
            $this->customerSessionMock,
            $this->entityFactoryMock
        );
    }

    public function testexecuteIfGiftRegistryEntityIdIsNull()
    {
        $registryItemId = 100;
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $dataObjectMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($dataObjectMock);

        $dataObjectMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($registryItemId);

        $entityMock = $this->createPartialMock(
            Entity::class,
            ['loadByEntityItem', 'getId']
        );
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($entityMock);

        $entityMock->expects($this->once())->method('loadByEntityItem')->with($registryItemId)->willReturnSelf();
        $entityMock->expects($this->once())->method('getId')->willReturn(null);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testexecute()
    {
        $prefix = 'prefix';
        $registryItemId = 100;
        $entityId = 200;
        $customerId = 300;
        $addressData = ['data' => 'value'];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $dataObjectMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getGiftregistryItemId', 'setId', 'setCustomerId'])
            ->onlyMethods(['addData'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($dataObjectMock);

        $dataObjectMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($registryItemId);

        $entityMock = $this->createPartialMock(
            Entity::class,
            ['loadByEntityItem', 'getId', 'exportAddress']
        );
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($entityMock);

        $entityMock->expects($this->once())->method('loadByEntityItem')->with($registryItemId)->willReturnSelf();
        $entityMock->expects($this->once())->method('getId')->willReturn($entityId);

        $customerMock = $this->createMock(Customer::class);
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $this->giftRegistryDataMock->expects($this->once())->method('getAddressIdPrefix')->willReturn($prefix);
        $this->customerSessionMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $exportedAddressMock = $this->createMock(Address::class);
        $exportedAddressMock->expects($this->once())->method('getData')->willReturn($addressData);
        $entityMock->expects($this->once())->method('exportAddress')->willReturn($exportedAddressMock);

        $dataObjectMock->expects($this->once())->method('setId')->with($prefix . $registryItemId)->willReturnSelf();
        $dataObjectMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $dataObjectMock->expects($this->once())->method('addData')->with($addressData)->willReturnSelf();

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
