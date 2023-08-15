<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Customer\Model\Address;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\ShippingMethodManagement;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\Data\EstimateAddressInterfaceFactory;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingMethodManagementTest extends TestCase
{
    /**
     * @var ShippingMethodManagement
     */
    private $model;

    /**
     * @var MockObject
     */
    private $entityFactoryMock;

    /**
     * Shipping method management
     *
     * @var MockObject
     */
    private $methodManagementMock;

    /**
     * Estimated address factory
     *
     * @var MockObject
     */
    private $addressFactoryMock;

    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockBuilder(EntityFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['__wakeup'])
            ->onlyMethods(['create'])
            ->getMock();
        $this->addressFactoryMock = $this->getMockBuilder(EstimateAddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['__wakeup'])
            ->onlyMethods(['create'])
            ->getMock();
        $this->methodManagementMock = $this->getMockForAbstractClass(ShippingMethodManagementInterface::class);
        $this->model = new ShippingMethodManagement(
            $this->entityFactoryMock,
            $this->methodManagementMock,
            $this->addressFactoryMock
        );
    }

    /**
     * @covers \Magento\GiftRegistry\Model\ShippingMethodManagement::estimateByRegistryId
     */
    public function testEstimateByRegistryId()
    {
        $cartId = 1;
        $giftRegistryId = 1;

        $giftRegistry = $this->createMock(Entity::class);
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($giftRegistry);
        $giftRegistry->expects($this->any())->method('getId')->willReturn($giftRegistryId);
        $giftRegistry->expects($this->once())->method('loadByEntityItem')->with($giftRegistryId);
        $giftRegistry->expects($this->any())->method('getId')->willReturn($giftRegistryId);

        $customerAddress = $this->createMock(Address::class);
        $giftRegistry->expects($this->once())->method('exportAddress')->willReturn($customerAddress);

        $estimatedAddress = $this->getMockForAbstractClass(EstimateAddressInterface::class);
        $estimatedAddress->expects($this->once())->method('setCountryId');
        $estimatedAddress->expects($this->once())->method('setPostcode');
        $estimatedAddress->expects($this->once())->method('setRegion');
        $estimatedAddress->expects($this->once())->method('setRegionId');

        $this->addressFactoryMock->expects($this->once())->method('create')->willReturn($estimatedAddress);

        $this->methodManagementMock->expects($this->once())
            ->method('estimateByAddress')
            ->with($cartId, $estimatedAddress);

        $this->model->estimateByRegistryId($cartId, $giftRegistryId);
    }

    /**
     * @covers \Magento\GiftRegistry\Model\ShippingMethodManagement::estimateByRegistryId
     */
    public function testEstimateByRegistryIdThrowsExceptionIfGiftRegistryIdIsNotValid()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Unknown gift registry identifier');
        $cartId = 1;
        $giftRegistryId = 1;

        $giftRegistry = $this->createMock(Entity::class);
        $giftRegistry->expects($this->once())->method('loadByEntityItem')->with($giftRegistryId);
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($giftRegistry);

        $this->model->estimateByRegistryId($cartId, $giftRegistryId);
    }
}
