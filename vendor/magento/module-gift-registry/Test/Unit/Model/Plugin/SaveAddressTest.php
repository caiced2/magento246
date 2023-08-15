<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Customer\Model\Session;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\Plugin\SaveAddress;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use Magento\Quote\Api\Data\AddressInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveAddressTest extends TestCase
{
    /**
     * @var SaveAddress
     */
    protected $model;

    /**
     * @var Entity|MockObject
     */
    protected $entityMock;

    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * Prepare testable object
     */
    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMock = $this->getMockBuilder(Entity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityFactoryMock = $this->getMockBuilder(EntityFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $entityFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->entityMock);

        /**
         * @var EntityFactory $entityFactoryMock
         */
        $this->model = new SaveAddress(
            $entityFactoryMock,
            $this->session
        );
    }

    /**
     * @test
     */
    public function testBeforeSaveAddressInformation()
    {
        $giftRegistryId = 1;
        $customerId = 10;
        $exportAddressData = ['street' => 'Baker Street'];
        $cartId = 42;

        $subject = $this->getMockForAbstractClass(ShippingInformationManagementInterface::class);
        $addressInfoMock = $this->getMockForAbstractClass(ShippingInformationInterface::class);
        $shippingAddressMock = $this->getMockForAbstractClass(
            AddressInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['importCustomerAddressData', 'setGiftregistryItemId']
        );
        $extensionAttributesMock = $this->getMockBuilder(AddressExtensionInterface::class)
            ->setMethods(['getGiftRegistryId'])
            ->getMockForAbstractClass();

        $addressInfoMock->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $shippingAddressMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        $extensionAttributesMock->expects($this->atLeastOnce())->method('getGiftRegistryId')
            ->willReturn($giftRegistryId);
        $this->entityMock->expects($this->once())->method('loadByEntityItem')->with($giftRegistryId)->willReturnSelf();
        $this->entityMock->expects($this->exactly(2))->method('getId')->willReturn($giftRegistryId);
        $this->session->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $shippingAddressMock->expects($this->once())->method('setCustomerAddressId')->with($customerId);
        $this->entityMock->expects($this->once())->method('exportAddressData')->willReturn($exportAddressData);
        $shippingAddressMock->expects($this->once())->method('importCustomerAddressData')->with($exportAddressData);
        $shippingAddressMock->expects($this->once())->method('setGiftregistryItemId')->with($giftRegistryId);

        $this->model->beforeSaveAddressInformation($subject, $cartId, $addressInfoMock);
    }
}
