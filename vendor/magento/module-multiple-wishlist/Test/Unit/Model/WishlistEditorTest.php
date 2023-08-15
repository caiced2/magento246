<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\MultipleWishlist\Helper\Data as HelperData;
use Magento\MultipleWishlist\Model\WishlistEditor;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection as WishlistCollection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory as WishlistCollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WishlistEditorTest extends TestCase
{
    /**
     * @var WishlistEditor|MockObject
     */
    protected $model;

    /**
     * @var WishlistCollectionFactory|MockObject
     */
    protected $wishlistCollectionFactory;

    /**
     * @var WishlistCollection|MockObject
     */
    protected $wishlistCollection;

    /**
     * @var WishlistFactory|MockObject
     */
    protected $wishlistFactory;

    /**
     * @var Wishlist|MockObject
     */
    protected $wishlist;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var HelperData|MockObject
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->prepareWishlistFactory();
        $this->prepareWishlistCollection();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder(\Magento\MultipleWishlist\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new WishlistEditor(
            $this->wishlistFactory,
            $this->customerSession,
            $this->wishlistCollectionFactory,
            $this->helper
        );
    }

    public function testEditWithNoCustomerId()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__('Sign in to edit wish lists.'));

        $this->model->edit(null, null);
    }

    public function testEditAnExistingWithWrongCustomer()
    {
        $customerId = 1;
        $wishlistId = 1;

        $this->wishlist->expects($this->once())
            ->method('load')
            ->with($wishlistId)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(0);

        $this->customerSession->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            (string)__('The wish list is not assigned to your account and can\'t be edited.')
        );

        $this->model->edit($customerId, null, false, $wishlistId);
    }

    public function testEditAnExisting()
    {
        $customerId = 1;
        $wishlistId = 1;
        $wishlistName = 'WishlistName';

        $this->wishlist->expects($this->once())
            ->method('load')
            ->with($wishlistId)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $this->wishlist->expects($this->once())
            ->method('setName')
            ->with($wishlistName)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('setVisibility')
            ->with(false)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->customerSession->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->model->edit($customerId, $wishlistName, false, $wishlistId);
    }

    public function testNewWithNoWishlistName()
    {
        $customerId = 1;
        $wishlistId = null;
        $wishlistName = '';

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__('Provide the wish list name.'));

        $this->model->edit($customerId, $wishlistName, false, $wishlistId);
    }

    public function testNewAlreadyExists()
    {
        $customerId = 1;
        $wishlistId = null;
        $wishlistName = 'WishlistName';

        $this->wishlistCollection->expects($this->once())
            ->method('filterByCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->wishlistCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('name', $wishlistName)
            ->willReturnSelf();
        $this->wishlistCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__('Wish list "%1" already exists.', $wishlistName));

        $this->model->edit($customerId, $wishlistName, false, $wishlistId);
    }

    public function testNewLimitReached()
    {
        $customerId = 1;
        $wishlistId = null;
        $wishlistName = 'WishlistName';
        $limit = 1;

        $this->wishlistCollection->expects($this->once())
            ->method('filterByCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->wishlistCollection->expects($this->never())
            ->method('addFieldToFilter')
            ->with('name', $wishlistName)
            ->willReturnSelf();
        $this->wishlistCollection->expects($this->never())
            ->method('getSize')
            ->willReturn(0);

        $this->helper->expects($this->once())
            ->method('getWishlistLimit')
            ->willReturn($limit);
        $this->helper->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistCollection)
            ->willReturn(true);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__('Only %1 wish list(s) can be created.', $limit));

        $this->model->edit($customerId, $wishlistName, false, $wishlistId);
    }

    public function testNew()
    {
        $customerId = 1;
        $wishlistId = null;
        $wishlistName = 'WishlistName';
        $limit = 1;

        $this->wishlistCollection->expects($this->once())
            ->method('filterByCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->wishlistCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('name', $wishlistName)
            ->willReturnSelf();
        $this->wishlistCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->helper->expects($this->once())
            ->method('getWishlistLimit')
            ->willReturn($limit);
        $this->helper->expects($this->once())
            ->method('isWishlistLimitReached')
            ->with($this->wishlistCollection)
            ->willReturn(false);

        $this->wishlist->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('generateSharingCode')
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('setName')
            ->with($wishlistName)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('setVisibility')
            ->with(false)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->model->edit($customerId, $wishlistName, false, $wishlistId);
    }

    protected function prepareWishlistFactory()
    {
        $this->wishlist = $this->getMockBuilder(Wishlist::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'getCustomerId',
                'setCustomerId',
                'setName',
                'setVisibility',
                'generateSharingCode',
                'save',
            ])
            ->getMock();

        $this->wishlistFactory = $this->getMockBuilder(WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->wishlistFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->wishlist);
    }

    protected function prepareWishlistCollection()
    {
        $this->wishlistCollection = $this->getMockBuilder(
            \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistCollectionFactory = $this->getMockBuilder(
            \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->wishlistCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->wishlistCollection);
    }
}
