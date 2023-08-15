<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\Observer\CartProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartProviderTest extends TestCase
{
    /**
     * @var CartProvider
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $cartMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $requestInterfaceMock;

    /**
     * @var MockObject
     */
    protected $sessionMock;

    /**
     * @var int
     */
    protected $soreId = 12;

    protected function setUp(): void
    {
        $this->cartMock = $this->createMock(Cart::class);
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getRequestModel', 'getSession'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestInterfaceMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->sessionMock =
            $this->getMockBuilder(Session::class)
                ->addMethods(['getStoreId', '_wakeup'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->model = new CartProvider($this->cartMock);
    }

    public function testGetStoreIdFromSession()
    {
        $this->observerMock->expects($this->exactly(2))
            ->method('getRequestModel')->willReturn($this->requestInterfaceMock);
        $this->requestInterfaceMock->expects($this->exactly(2))
            ->method('getParam')->willReturn(null);
        $this->observerMock->expects($this->exactly(2))
            ->method('getSession')->willReturn($this->sessionMock);
        $this->sessionMock->expects($this->once())->method('getStoreId')->willReturn($this->soreId);
        $this->cartMock->expects($this->once())->method('setSession')
            ->with($this->sessionMock)->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_ORDER)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('setCurrentStore')
            ->with($this->soreId)->willReturnSelf();

        $this->model->get($this->observerMock);
    }

    public function testGet()
    {
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')->willReturn($this->requestInterfaceMock);
        $this->requestInterfaceMock->expects($this->once())
            ->method('getParam')->willReturn($this->soreId);
        $this->observerMock->expects($this->once())
            ->method('getSession')->willReturn($this->sessionMock);
        $this->cartMock->expects($this->once())->method('setSession')
            ->with($this->sessionMock)->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_ORDER)->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setCurrentStore')->with($this->soreId);

        $this->model->get($this->observerMock);
    }
}
