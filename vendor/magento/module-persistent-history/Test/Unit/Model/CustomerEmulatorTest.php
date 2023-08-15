<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Persistent\Helper\Data;
use Magento\Persistent\Helper\Session;
use Magento\Persistent\Observer\EmulateCustomerObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerEmulatorTest extends TestCase
{
    /**
     * @var EmulateCustomerObserver
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var MockObject
     */
    protected $helperMock;

    /**
     * @var MockObject
     */
    protected $customerSessionMock;

    /**
     * @var MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var MockObject
     */
    protected $defaultShippingAddressMock;

    /**
     * @var MockObject
     */
    protected $defaultBillingAddressMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $customerMock;

    /**
     * @var MockObject
     */
    protected $persistentSessionMock;

    /**
     * @var MockObject
     */
    protected $addressRepositoryMock;

    protected function setUp(): void
    {
        $this->persistentHelperMock = $this->createMock(Session::class);
        $this->helperMock = $this->createMock(Data::class);

        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->addMethods(['setDefaultTaxShippingAddress', 'setDefaultTaxBillingAddress', 'setIsCustomerEmulated'])
            ->onlyMethods(['setCustomerId', 'setCustomerGroupId', 'isLoggedIn'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepositoryMock = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->defaultShippingAddressMock = $this->getMockBuilder(Address::class)
            ->addMethods(['getCountryId', 'getPostcode'])
            ->onlyMethods(['getRegion', 'getRegionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->defaultBillingAddressMock = $this->getMockBuilder(Address::class)
            ->addMethods(['getCountryId', 'getPostcode'])
            ->onlyMethods(['getRegion', 'getRegionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this->createMock(Observer::class);
        $this->customerMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->persistentSessionMock = $this->getMockBuilder(\Magento\Persistent\Model\Session::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressRepositoryMock = $this->getMockForAbstractClass(AddressRepositoryInterface::class);
        $this->model = new EmulateCustomerObserver(
            $this->persistentHelperMock,
            $this->helperMock,
            $this->customerSessionMock,
            $this->customerRepositoryMock,
            $this->addressRepositoryMock
        );
    }

    public function testExecuteWhenCannotProcessPersistentData()
    {
        $this->helperMock->expects($this->once())->method('canProcess')->with($this->observerMock)->willReturn(false);
        $this->helperMock->expects($this->never())->method('isShoppingCartPersist');
        $this->model->execute($this->observerMock);
    }

    public function testExecuteWhenShoppingCartNotPersisted()
    {
        $this->helperMock->expects($this->once())->method('canProcess')->with($this->observerMock)->willReturn(true);
        $this->helperMock->expects($this->once())->method('isShoppingCartPersist')->willReturn(false);
        $this->model->execute($this->observerMock);
    }

    public function testExecuteWhenCustomerLoggedIn()
    {
        $this->helperMock->expects($this->once())->method('canProcess')->with($this->observerMock)->willReturn(true);
        $this->helperMock->expects($this->once())->method('isShoppingCartPersist')->willReturn(true);
        $this->persistentHelperMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->customerRepositoryMock->expects($this->never())->method('getById');
        $this->model->execute($this->observerMock);
    }

    public function testExecuteWhenCustomerHasAddresses()
    {
        $customerId = 1;
        $countryId = 3;
        $regionId = 4;
        $postcode = 90210;
        $customerGroupId = 2;
        $this->helperMock->expects($this->once())->method('canProcess')->with($this->observerMock)->willReturn(true);
        $this->helperMock->expects($this->once())->method('isShoppingCartPersist')->willReturn(true);
        $this->persistentHelperMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->persistentHelperMock
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($this->persistentSessionMock);
        $this->persistentSessionMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customerMock);
        $this->customerMock
            ->expects($this->once())
            ->method('getDefaultShipping')
            ->willReturn(12345);
        $this->customerMock
            ->expects($this->once())
            ->method('getDefaultBilling')
            ->willReturn(12346);
        $valueMap = [
            [12345, $this->defaultShippingAddressMock],
            [12346, $this->defaultBillingAddressMock]
        ];
        $this->addressRepositoryMock->expects($this->any())->method('getById')->willReturnMap($valueMap);
        $this->defaultBillingAddressMock->expects($this->once())->method('getCountryId')->willReturn($countryId);
        $this->defaultBillingAddressMock->expects($this->once())->method('getRegion')->willReturn('California');
        $this->defaultBillingAddressMock->expects($this->once())->method('getRegionId')->willReturn($regionId);
        $this->defaultBillingAddressMock->expects($this->once())->method('getPostcode')->willReturn($postcode);
        $this->defaultShippingAddressMock->expects($this->once())->method('getCountryId')->willReturn($countryId);
        $this->defaultShippingAddressMock->expects($this->once())->method('getRegion')->willReturn('California');
        $this->defaultShippingAddressMock->expects($this->once())->method('getRegionId')->willReturn($regionId);
        $this->defaultShippingAddressMock->expects($this->once())->method('getPostcode')->willReturn($postcode);
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setDefaultTaxShippingAddress')
            ->with(
                [
                    'country_id' => $countryId,
                    'region_id' => $regionId,
                    'postcode' => $postcode
                ]
            );
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setDefaultTaxBillingAddress')
            ->with(
                [
                    'country_id' => $countryId,
                    'region_id' => $regionId,
                    'postcode' => $postcode
                ]
            );
        $this->customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $this->customerMock->expects($this->once())->method('getGroupId')->willReturn($customerGroupId);
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setCustomerGroupId')
            ->with($customerGroupId)
            ->willReturnSelf();
        $this->model->execute($this->observerMock);
    }

    public function testExecuteWhenCustomerDoesnotHaveAddress()
    {
        $customerId = 1;
        $customerGroupId = 2;
        $this->helperMock->expects($this->once())->method('canProcess')->with($this->observerMock)->willReturn(true);
        $this->helperMock->expects($this->once())->method('isShoppingCartPersist')->willReturn(true);
        $this->persistentHelperMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->persistentHelperMock
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($this->persistentSessionMock);
        $this->persistentSessionMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customerMock);
        $this->customerMock
            ->expects($this->once())
            ->method('getDefaultShipping')
            ->willReturn(null);
        $this->customerMock
            ->expects($this->once())
            ->method('getDefaultBilling')
            ->willReturn(null);
        $this->customerSessionMock
            ->expects($this->never())
            ->method('setDefaultTaxShippingAddress');
        $this->customerSessionMock
            ->expects($this->never())
            ->method('setDefaultTaxBillingAddress');
        $this->customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $this->customerMock->expects($this->once())->method('getGroupId')->willReturn($customerGroupId);
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setCustomerGroupId')
            ->with($customerGroupId)->willReturnSelf();
        $this->model->execute($this->observerMock);
    }
}
