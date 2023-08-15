<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Model\Search\Strategy;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\Email;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection;
use Magento\Wishlist\Model\Wishlist;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    /** @var StoreManagerInterface|MockObject */
    protected $storeManager;

    /** @var CustomerFactory|MockObject */
    protected $customerFactory;

    /** @var Email */
    protected $model;

    protected function setUp(): void
    {
        $this->storeManager = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->customerFactory = $this->createPartialMock(CustomerFactory::class, ['create']);
        $this->model = new Email($this->customerFactory, $this->storeManager);
    }

    public function testSetSearchParamsWithException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Please enter a valid email address.');
        $this->model->setSearchParams([]);
    }

    public function testFilterCollection()
    {
        $collection = $this->createMock(Collection::class);
        $store = $this->createMock(Store::class);
        $customer = $this->getMockBuilder(Customer::class)
            ->addMethods(['setWebsiteId'])
            ->onlyMethods(['getId', 'loadByEmail'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willReturn($customer);
        $customer->expects($this->once())
            ->method('setWebsiteId')
            ->willReturnSelf();
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);
        $store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);
        $customer->expects($this->once())
            ->method('loadByEmail')
            ->willReturnSelf();
        $customer->expects($this->once())
            ->method('getId')
            ->willReturn(23);

        $wishlist = $this->getMockBuilder(Wishlist::class)
            ->addMethods(['setCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $wishlist->expects($this->once())
            ->method('setCustomer')
            ->with($customer);
        $iterator = new \ArrayObject([$wishlist]);
        $collection->expects($this->once())
            ->method('filterByCustomerId')
            ->with(23);
        $collection->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $this->assertSame($collection, $this->model->filterCollection($collection));
    }
}
