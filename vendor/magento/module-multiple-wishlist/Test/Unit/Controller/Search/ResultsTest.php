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
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\MultipleWishlist\Controller\Search\Results;
use Magento\MultipleWishlist\Model\Search;
use Magento\MultipleWishlist\Model\Search\Strategy\Email;
use Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\Name;
use Magento\MultipleWishlist\Model\Search\Strategy\NameFactory;
use Magento\MultipleWishlist\Model\SearchFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResultsTest extends TestCase
{
    /**
     * @var Results
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
     * @var WishlistFactory|MockObject
     */
    protected $wishlistFactoryMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var Session|MockObject
     */
    protected $customerSessionMock;

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
     * @var ViewInterface|MockObject
     */
    protected $viewMock;

    /**
     * @var EmailFactory|MockObject
     */
    protected $strategyEmailFactoryMock;

    /**
     * @var NameFactory|MockObject
     */
    protected $strategyNameFactoryMock;

    /**
     * @var SearchFactory|MockObject
     */
    protected $searchFactoryMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->wishlistFactoryMock = $this->getMockBuilder(WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemFactoryMock = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchFactoryMock = $this->getMockBuilder(SearchFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->strategyEmailFactoryMock = $this->getMockBuilder(
            EmailFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $this->strategyNameFactoryMock = $this->getMockBuilder(
            NameFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutCartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setLastWishlistSearchParams'])
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

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->viewMock = $this->getMockBuilder(ViewInterface::class)
            ->getMock();

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
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->moduleManagerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Results(
            $this->contextMock,
            $this->registryMock,
            $itemFactoryMock,
            $this->wishlistFactoryMock,
            $this->searchFactoryMock,
            $this->strategyEmailFactoryMock,
            $this->strategyNameFactoryMock,
            $checkoutSessionMock,
            $checkoutCartMock,
            $this->customerSessionMock,
            $localeResolverMock,
            $this->moduleManagerMock
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithType()
    {
        $search = 'type';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Name|MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(Name::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyNameFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyEmailFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        /** @var Collection|MockObject $strategyMock */
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willReturn($collectionMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('search_results', $collectionMock)
            ->willReturn($searchMock);

        $this->customerSessionMock->expects($this->once())
            ->method('setLastWishlistSearchParams')
            ->with($params);

        /** @var Config|MockObject $strategyMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var Title|MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithEmail()
    {
        $search = 'email';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Email|MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyEmailFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyNameFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        /** @var Collection|MockObject $strategyMock */
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willReturn($collectionMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('search_results', $collectionMock)
            ->willReturn($searchMock);

        $this->customerSessionMock->expects($this->once())
            ->method('setLastWishlistSearchParams')
            ->with($params);

        /** @var Config|MockObject $strategyMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var Title|MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithException()
    {
        $search = 'email';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Email|MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyEmailFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyNameFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        $exception = new \Exception('Exception');

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willThrowException($exception);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('We could not perform the search.'))
            ->willReturnSelf();

        /** @var Config|MockObject $strategyMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var Title|MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithInvalidArgumentException()
    {
        $search = 'email';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        /** @var Email|MockObject $strategyMock */
        $strategyMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSearchParams'])
            ->getMock();

        $this->strategyEmailFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($strategyMock);

        $this->strategyNameFactoryMock->expects($this->never())
            ->method('create');

        $strategyMock->expects($this->once())
            ->method('setSearchParams')
            ->with($params);

        /** @var \Magento\MultipleWishlist\Model\Search|MockObject $strategyMock */
        $searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchMock);

        $exception = new \InvalidArgumentException('InvalidArgumentException');

        $searchMock->expects($this->once())
            ->method('getResults')
            ->with($strategyMock)
            ->willThrowException($exception);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addNotice')
            ->with(__('InvalidArgumentException'))
            ->willReturnSelf();

        /** @var Config|MockObject $strategyMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var Title|MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithWrongParam()
    {
        $search = 'wrong_param';
        $params = [
            'search' => $search,
        ];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Please reenter your search options.'))
            ->willReturnSelf();

        /** @var Config|MockObject $strategyMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var Title|MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithNoParams()
    {
        $params = [];

        $this->viewMock->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('params', null)
            ->willReturn($params);

        $this->registryMock->expects($this->never())
            ->method('register');

        $this->customerSessionMock->expects($this->never())
            ->method('setLastWishlistSearchParams');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Please reenter your search options.'))
            ->willReturnSelf();

        /** @var Config|MockObject $strategyMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        /** @var Title|MockObject $strategyMock */
        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Wish List Search'))
            ->willReturnSelf();

        $this->assertEquals($this->resultPageMock, $this->model->execute());
    }
}
