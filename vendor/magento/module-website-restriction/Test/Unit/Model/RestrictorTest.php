<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Test\Unit\Model;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Session\Generic;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Framework\UrlFactory;
use Magento\WebsiteRestriction\Model\ConfigInterface;
use Magento\WebsiteRestriction\Model\Mode;
use Magento\WebsiteRestriction\Model\Restrictor;
use PHPUnit\Framework\TestCase;

/**
 * Class test website restriction functionality
 */
class RestrictorTest extends TestCase
{
    /**
     * @var Restrictor
     */
    protected $model;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var UrlFactory
     */
    protected $urlFactory;

    /**
     * @var Generic
     */
    protected $session;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->config->expects($this->once())
            ->method('getMode')
            ->willReturn(Mode::ALLOW_LOGIN);

        $customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->session = $this->getMockBuilder(Generic::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlFactory = $this->getMockBuilder(UrlFactory::class)->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->customerUrl = $this->getMockBuilder(CustomerUrl::class)->disableOriginalConstructor()
            ->onlyMethods(['getLoginUrl'])
            ->getMock();

        $this->model = $objectManager->getObject(
            Restrictor::class,
            [
                'config' => $this->config,
                'session' => $this->session,
                'urlFactory' => $this->urlFactory,
                'customerSession' => $customerSession,
                'customerUrl' => $this->customerUrl
            ]
        );
    }

    /**
     * Test to restrict 302 with landing.
     *
     * @return void
     */
    public function testRestrictRedirectNot302Landing(): void
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getFullActionName', 'setControllerName'])
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getFullActionName')
            ->willReturn('test_action');
        $responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setRedirect'])
            ->getMockForAbstractClass();
        $isCustomerLoggedIn = false;

        $this->config->expects($this->once())
            ->method('getGenericActions')
            ->willReturn(['generic_Actions']);
        $this->config->expects($this->once())
            ->method('getHTTPRedirectCode')
            ->willReturn(0);

        $urlValue = 'url_value';
        $this->customerUrl->expects($this->once())
            ->method('getLoginUrl')
            ->willReturn($urlValue);

        $responseMock->expects($this->once())
            ->method('setRedirect')
            ->willReturn($urlValue);

        $this->model->restrict($requestMock, $responseMock, $isCustomerLoggedIn);
    }

    /**
     * Test to restrict 302 not landing.
     *
     * @return void
     */
    public function testRestrictRedirect302Landing(): void
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getFullActionName', 'setControllerName'])
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getFullActionName')
            ->willReturn('test_action');
        $responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setRedirect'])
            ->getMockForAbstractClass();
        $isCustomerLoggedIn = false;

        $this->config->expects($this->once())
            ->method('getGenericActions')
            ->willReturn(['generic_Actions']);
        $this->config->expects($this->once())
            ->method('getHTTPRedirectCode')
            ->willReturn(Mode::HTTP_302_LANDING);

        $landingPageCode = 'landing_page_code';
        $this->config->expects($this->once())
            ->method('getLandingPageCode')
            ->willReturn($landingPageCode);

        $urlMock = $this->getMockBuilder(Url::class)->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();

        $this->urlFactory
            ->method('create')
            ->willReturn($urlMock);

        $urlValue = 'url_value';
        $urlMock->expects($this->once())
            ->method('getUrl')
            ->with('', ['_direct' => $landingPageCode])
            ->willReturn($urlValue);

        $responseMock->expects($this->once())
            ->method('setRedirect')
            ->willReturn($urlValue);

        $this->model->restrict($requestMock, $responseMock, $isCustomerLoggedIn);
    }
}
