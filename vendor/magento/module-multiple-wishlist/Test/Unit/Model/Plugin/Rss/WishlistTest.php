<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Model\Plugin\Rss;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\MultipleWishlist\Helper\Rss;
use Magento\MultipleWishlist\Model\Plugin\Rss\Wishlist;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WishlistTest extends TestCase
{
    /** @var Wishlist */
    protected $wishlist;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Rss|MockObject */
    protected $helper;

    /** @var UrlInterface|MockObject */
    protected $urlInterface;

    /** @var ScopeConfigInterface|MockObject */
    protected $scopeConfig;

    /** @var View|MockObject */
    protected $customerViewHelper;

    /** @var CustomerRepositoryInterface|MockObject */
    protected $customerRepository;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Rss::class);
        $this->urlInterface = $this->getMockForAbstractClass(UrlInterface::class);

        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->customerViewHelper = $this->createMock(View::class);
        $this->customerRepository = $this->getMockForAbstractClass(
            CustomerRepositoryInterface::class,
            [],
            '',
            false
        );

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wishlist = $this->objectManagerHelper->getObject(
            Wishlist::class,
            [
                'wishlistHelper' => $this->helper,
                'urlBuilder' => $this->urlInterface,
                'scopeConfig' => $this->scopeConfig,
                'customerViewHelper' => $this->customerViewHelper,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * @dataProvider aroundGetHeaderDataProvider
     *
     * @param bool $multipleEnabled
     * @param int $customerId
     * @param bool $isDefault
     * @param array $expectedResult
     */
    public function testAroundGetHeader($multipleEnabled, $customerId, $isDefault, $expectedResult)
    {
        $subject = $this->createMock(\Magento\Wishlist\Model\Rss\Wishlist::class);
        $wishlist = $this->getMockBuilder(\Magento\Wishlist\Model\Wishlist::class)->addMethods(['getSharingCode'])
            ->onlyMethods(['getId', 'getCustomerId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $wishlist->expects($this->any())->method('getId')->willReturn(5);
        $wishlist->expects($this->any())->method('getCustomerId')->willReturn(8);
        $wishlist->expects($this->any())->method('getName')->willReturn('Wishlist1');
        $wishlist->expects($this->any())->method('getSharingCode')->willReturn('code');

        $customer = $this->getMockForAbstractClass(CustomerInterface::class, [], '', false);
        $customer->expects($this->any())->method('getId')->willReturn($customerId);
        $customer->expects($this->any())->method('getEmail')->willReturn('test@example.com');

        $this->helper->expects($this->any())->method('getWishlist')->willReturn($wishlist);
        $this->helper->expects($this->any())->method('getCustomer')->willReturn($customer);
        $this->helper->expects($this->any())->method('isWishlistDefault')->willReturn($isDefault);
        $this->helper->expects($this->any())->method('getDefaultWishlistName')->willReturn('Wishlist1');

        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->with('wishlist/general/multiple_active', ScopeInterface::SCOPE_STORE)
            ->willReturn($multipleEnabled);

        $this->customerRepository
            ->expects($this->any())
            ->method('getById')
            ->with(8)
            ->willReturn($customer);

        $this->customerViewHelper->expects($this->any())->method('getCustomerName')->with($customer)
            ->willReturn('Customer1');

        $this->urlInterface
            ->expects($this->any())
            ->method('getUrl')
            ->with('wishlist/shared/index', ['code' => 'code'])
            ->willReturn('http://url.com/rss/feed/index/type/wishlist/wishlist_id/5');

        $proceed = function () use ($expectedResult) {
            return $expectedResult;
        };

        $this->assertEquals($expectedResult, $this->wishlist->aroundGetHeader($subject, $proceed));
    }

    public function aroundGetHeaderDataProvider()
    {
        return [
            [false, 8, true, [
                'title' => 'title',
                'description' => 'title',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
            [true, 8, true, [
                'title' => 'Customer1\'s Wish List',
                'description' => 'Customer1\'s Wish List',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
            [true, 8, false, [
                'title' => 'Customer1\'s Wish List (Wishlist1)',
                'description' => 'Customer1\'s Wish List (Wishlist1)',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
            [true, 9, false, [
                'title' => 'Customer1\'s Wish List (Wishlist1)',
                'description' => 'Customer1\'s Wish List (Wishlist1)',
                'link' => 'http://url.com/rss/feed/index/type/wishlist/wishlist_id/5',
                'charset' => 'UTF-8'
            ]],
        ];
    }
}
