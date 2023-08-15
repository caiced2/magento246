<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Block\Checkout\Cart;

use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\GiftCardAccount\Block\Checkout\Cart\Giftcardaccount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardaccountTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $customerSessionMock;

    /**
     * @var MockObject
     */
    protected $checkoutSession;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var MockObject
     */
    protected $eventManagerMock;

    /**
     * @var Giftcardaccount
     */
    protected $model;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->checkoutSession = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->contextMock->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->atLeastOnce())->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->contextMock->expects($this->any())->method('getEventManager')->willReturn($this->eventManagerMock);
        $this->model = new Giftcardaccount(
            $this->contextMock,
            $this->customerSessionMock,
            $this->checkoutSession
        );
    }

    public function testGetUrlNoParam()
    {
        $route = 'someroute';
        $params = [];
        $secureFlag = true;
        $builderResult = 'secureURL';

        $this->requestMock->expects($this->once())->method('isSecure')->willReturn($secureFlag);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($route, ['_secure' => $secureFlag])
            ->willReturn($builderResult);
        $url = $this->model->getUrl($route, $params);
        $this->assertEquals($builderResult, $url);
    }

    public function testGetUrlWithParam()
    {
        $route = 'someroute';
        $params = ['_secure' => true];
        $builderResult = 'secureURL';

        $this->requestMock->expects($this->never())->method('isSecure');
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($route, $params)
            ->willReturn($builderResult);
        $url = $this->model->getUrl($route, $params);
        $this->assertEquals($builderResult, $url);
    }
}
