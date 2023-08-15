<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\Backend\Model\Auth\Session;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerInterface;
use Magento\Catalog\Model\Product;
use Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\PricePermissions;
use Magento\PricePermissions\Helper\Data;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PricePermissionsTest extends TestCase
{
    /**
     * @var PricePermissions
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $authSessionMock;

    /**
     * @var MockObject
     */
    protected $pricePermDataMock;

    /**
     * @var MockObject
     */
    protected $productHandlerMock;

    /**
     * @var MockObject
     */
    protected $productMock;

    /**
     * @var MockObject
     */
    protected $userMock;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    protected function setUp(): void
    {
        $this->authSessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['getUser'])
            ->onlyMethods(['isLoggedIn'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pricePermDataMock = $this->createMock(Data::class);
        $this->productMock = $this->createMock(Product::class);
        $this->productHandlerMock = $this->createMock(
            HandlerInterface::class
        );
        $this->userMock = $this->createMock(User::class);

        $this->subjectMock = $this->createMock(
            Helper::class
        );
        $this->_model = new PricePermissions(
            $this->authSessionMock,
            $this->pricePermDataMock,
            $this->productHandlerMock
        );
    }

    public function testAfterInitializeWithNotLoggedInUser()
    {
        $this->authSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->pricePermDataMock->expects($this->never())->method('getCanAdminEditProductPrice');

        $this->productHandlerMock->expects($this->once())->method('handle')->with($this->productMock);

        $this->assertEquals(
            $this->productMock,
            $this->_model->afterInitialize($this->subjectMock, $this->productMock)
        );
    }

    public function testAfterInitializeWithUserWithoutRole()
    {
        $this->userMock->expects($this->once())->method('getRole')->willReturn(null);
        $this->authSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->authSessionMock->expects($this->once())->method('getUser')->willReturn($this->userMock);
        $this->pricePermDataMock->expects($this->never())->method('getCanAdminEditProductPrice');
        $this->productHandlerMock->expects($this->once())->method('handle')->with($this->productMock);

        $this->assertEquals(
            $this->productMock,
            $this->_model->afterInitialize($this->subjectMock, $this->productMock)
        );
    }

    public function testAfterInitializeWhenAdminCanNotEditProductPrice()
    {
        $this->userMock->expects($this->once())->method('getRole')->willReturn(1);
        $this->authSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->authSessionMock->expects($this->once())->method('getUser')->willReturn($this->userMock);
        $this->pricePermDataMock->expects(
            $this->once()
        )->method(
            'getCanAdminEditProductPrice'
        )->willReturn(
            false
        );

        $this->productHandlerMock->expects($this->once())->method('handle')->with($this->productMock);

        $this->assertEquals(
            $this->productMock,
            $this->_model->afterInitialize($this->subjectMock, $this->productMock)
        );
    }

    public function testAfterInitializeWhenAdminCanEditProductPrice()
    {
        $this->userMock->expects($this->once())->method('getRole')->willReturn(1);
        $this->authSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->authSessionMock->expects($this->once())->method('getUser')->willReturn($this->userMock);
        $this->pricePermDataMock->expects(
            $this->once()
        )->method(
            'getCanAdminEditProductPrice'
        )->willReturn(
            true
        );
        $this->productHandlerMock->expects($this->never())->method('handle');

        $this->assertEquals(
            $this->productMock,
            $this->_model->afterInitialize($this->subjectMock, $this->productMock)
        );
    }
}
