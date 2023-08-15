<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test class for \Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Management
 */
namespace Magento\MultipleWishlist\Test\Unit\Block\Customer\Wishlist\Item\Column;

use Magento\Catalog\Block\Product\Context;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Management;
use Magento\MultipleWishlist\Helper\Data;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection;
use Magento\Wishlist\Model\Wishlist;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManagementTest extends TestCase
{
    /**
     * @var Management
     */
    protected $model;

    /**
     * @var MockObject|Collection
     */
    protected $wishlistListMock;

    /**
     * @var Data|MockObject
     */
    protected $wishlistHelperMock;

    /**
     * @var CustomerInterface|MockObject
     */
    protected $dataCustomerMock;

    /**
     * @var Wishlist|MockObject
     */
    protected $wishlistMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->dataCustomerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->wishlistHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistMock = $this->getMockBuilder(Wishlist::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistListMock = $objectManagerHelper->getCollectionMock(
            Collection::class,
            [$this->wishlistMock]
        );

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->once())
            ->method('getWishlistHelper')
            ->willReturn($this->wishlistHelperMock);

        $this->model = $objectManagerHelper->getObject(
            Management::class,
            ['context' => $this->contextMock]
        );
    }

    public function testCanCreateWishlistsNoCustomer()
    {
        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn(false);

        $this->assertFalse($this->model->canCreateWishlists($this->wishlistListMock));
    }

    public function testCanCreateWishlists()
    {
        $this->dataCustomerMock->expects($this->once())
            ->method('getId')
            ->willReturn(true);

        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->dataCustomerMock);
        $this->wishlistHelperMock->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistListMock)
            ->willReturn(false);

        $this->assertTrue($this->model->canCreateWishlists($this->wishlistListMock));
    }

    public function testCanCreateWishlistsLimitReached()
    {
        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->dataCustomerMock);
        $this->wishlistHelperMock->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistListMock)
            ->willReturn(true);

        $this->assertFalse($this->model->canCreateWishlists($this->wishlistListMock));
    }

    public function testCanCreateWishlistsNoCustomerId()
    {
        $this->dataCustomerMock->expects($this->once())
            ->method('getId')
            ->willReturn(false);

        $this->wishlistHelperMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->dataCustomerMock);
        $this->wishlistHelperMock->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistListMock)
            ->willReturn(false);

        $this->assertFalse($this->model->canCreateWishlists($this->wishlistListMock));
    }
}
