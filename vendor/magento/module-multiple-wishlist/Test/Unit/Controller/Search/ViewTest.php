<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Controller\Search;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\MultipleWishlist\Controller\Search\View;
use Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\NameFactory;
use Magento\MultipleWishlist\Model\SearchFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends TestCase
{
    /**
     * @var View
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var RedirectInterface|MockObject
     */
    protected $redirectMock;

    /**
     * @var Wishlist|MockObject
     */
    protected $wishlistMock;

    /**
     * @var WishlistFactory|MockObject
     */
    protected $wishlistFactorytMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var Session|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var Layout|MockObject
     */
    protected $layoutMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $blockMock;

    /**
     * @var Manager|MockObject
     */
    protected $moduleManagerMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->wishlistMock = $this->getMockBuilder(Wishlist::class)
            ->onlyMethods(['load', 'getId', 'getCustomerId'])
            ->addMethods(['getVisibility'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->wishlistFactorytMock = $this->getMockBuilder(WishlistFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->wishlistFactorytMock->expects($this->any())
            ->method('create')
            ->willReturn($this->wishlistMock);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemFactoryMock = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $searchFactoryMock = $this->getMockBuilder(SearchFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $strategyEmailFactoryMock = $this->getMockBuilder(EmailFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $strategyNameFactoryMock = $this->getMockBuilder(NameFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutCartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localeResolverMock = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $responseMock = $this->getMockBuilder(HttpInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->redirectMock = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->layoutMock = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml'])
            ->addMethods(['setRefererUrl'])
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE, [])
            ->willReturn($this->resultPageMock);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($responseMock);
        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->willReturn($this->redirectMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->moduleManagerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new View(
            $this->contextMock,
            $this->registryMock,
            $itemFactoryMock,
            $this->wishlistFactorytMock,
            $searchFactoryMock,
            $strategyEmailFactoryMock,
            $strategyNameFactoryMock,
            $checkoutSessionMock,
            $checkoutCartMock,
            $this->customerSessionMock,
            $localeResolverMock,
            $this->moduleManagerMock
        );
    }

    /**
     * @return void
     */
    public function testExecuteNotFoundFirst(): void
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->requestMock
            ->method('getParam')
            ->with('wishlist_id')
            ->willReturn(false);

        $this->model->execute();
    }

    /**
     * @param int $wishlistId
     * @param int $visibility
     * @param int $customerId
     *
     * @return void
     * @dataProvider getNotFoundParametersDataProvider
     */
    public function testExecuteNotFoundSecond(int $wishlistId, int $visibility, int $customerId): void
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('wishlist_id')
            ->willReturn(true);

        $this->wishlistMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->wishlistMock->expects($this->any())
            ->method('getId')
            ->willReturn($wishlistId);
        $this->wishlistMock->expects($this->any())
            ->method('getVisibility')
            ->willReturn($visibility);
        $this->wishlistMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);

        $this->model->execute();
    }

    /**
     * @return array
     */
    public function getNotFoundParametersDataProvider(): array
    {
        return [
            [0, 0, 0],
            [1, 0, 0],
            [0, 1, 0]
        ];
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $wishlistId = 1;

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('wishlist_id')
            ->willReturn(true);

        $this->wishlistMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $this->wishlistMock->expects($this->any())
            ->method('getId')
            ->willReturn($wishlistId);
        $this->wishlistMock->expects($this->any())
            ->method('getVisibility')
            ->willReturn(1);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('shared_wishlist', $this->wishlistMock)
            ->willReturn(1);

        $this->resultPageMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->blockMock->expects($this->once())
            ->method('setRefererUrl')
            ->willReturnMap([
                ['', $this->layoutMock]
            ]);

        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->willReturnMap([
                ['customer.wishlist.info', $this->blockMock]
            ]);

        $this->redirectMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn('');

        $this->assertInstanceOf(
            Page::class,
            $this->model->execute()
        );
    }
}
