<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Model;

use Magento\Catalog\Helper\Product\Compare;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\Persistent\Model\Session as PersistentSession;
use Magento\PersistentHistory\Model\CustomerEmulator;
use Magento\Wishlist\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerEmulatorObserverTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var MockObject
     */
    private $compareProductHelperMock;

    /**
     * @var MockObject
     */
    private $persistentSessionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $wishlistHelperMock;

    /**
     * @var MockObject
     */
    private $persistentHistoryMock;

    /**
     * @var MockObject
     */
    private $registryMock;

    /**
     * @var MockObject
     */
    private $customerFactoryMock;

    /**
     * @var MockObject
     */
    private $customerSessionMock;

    /**
     * @var MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var MockObject
     */
    private $addressRepositoryMock;

    /**
     * @var CustomerEmulator
     */
    private $model;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->compareProductHelperMock = $this->createMock(Compare::class);

        $this->persistentSessionMock = $this->createMock(Session::class);
        $this->wishlistHelperMock = $this->createMock(Data::class);
        $this->persistentHistoryMock = $this->createMock(\Magento\PersistentHistory\Helper\Data::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->customerFactoryMock = $this->createPartialMock(
            CustomerFactory::class,
            ['create']
        );
        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->addMethods(['setIsCustomerEmulated'])
            ->onlyMethods(['setCustomerId', 'setCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepositoryMock = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);

        $this->addressRepositoryMock = $this->getMockForAbstractClass(AddressRepositoryInterface::class);
        $this->model = new CustomerEmulator(
            $this->persistentSessionMock,
            $this->wishlistHelperMock,
            $this->persistentHistoryMock,
            $this->registryMock,
            $this->customerFactoryMock,
            $this->customerSessionMock,
            $this->customerRepositoryMock,
            $this->addressRepositoryMock
        );
        $this->objectManagerHelper->setBackwardCompatibleProperty(
            $this->model,
            'compareProductHelper',
            $this->compareProductHelperMock
        );
    }

    public function testEmulate()
    {
        $customerId = 1;
        $persistentSession = $this->getMockBuilder(PersistentSession::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock = $this->getMockBuilder(Customer::class)
            ->addMethods(['getDefaultShipping', 'getDefaultBilling'])
            ->onlyMethods(['load', 'getGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock->expects($this->once())->method('getGroupId')->willReturn(2);
        $this->persistentSessionMock->expects($this->once())->method('getSession')->willReturn($persistentSession);
        $persistentSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerFactoryMock->expects($this->once())->method('create')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('load')->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->once())->method('setCustomerGroupId')->with(2)->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('setIsCustomerEmulated')
            ->with(true)
            ->willReturnSelf();
        $this->persistentHistoryMock->expects($this->once())->method('isCompareProductsPersist')->willReturn(true);
        $this->compareProductHelperMock->expects($this->once())->method('setCustomerId')->with($customerId);
        $this->model->emulate();
    }
}
