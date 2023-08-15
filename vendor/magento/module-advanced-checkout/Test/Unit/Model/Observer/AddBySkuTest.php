<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

use Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\Observer\AddBySku;
use Magento\AdvancedCheckout\Model\Observer\CartProvider;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\AdminOrder\Create;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddBySkuTest extends TestCase
{
    /**
     * @var AddBySku
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $cartMock;

    /**
     * @var MockObject
     */
    protected $cartProviderMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $orderCreateModelMock;

    protected function setUp(): void
    {
        $this->cartMock = $this->createMock(Cart::class);
        $this->cartProviderMock = $this->getMockBuilder(CartProvider::class)
            ->addMethods(
                [
                    'removeAllAffectedItems',
                    'removeAffectedItem',
                    'prepareAddProductBySku',
                    'saveAffectedProducts',
                    'setCurrentStore'
                ]
            )
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getRequestModel', 'getOrderCreateModel'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(
                [
                    'getPost',
                    'setPostValue',
                    '__wakeup'
                ]
            )
            ->getMockForAbstractClass();
        $this->orderCreateModelMock = $this->createMock(Create::class);

        $this->model = new AddBySku($this->cartMock, $this->cartProviderMock);
    }

    public function testExecuteWithEmptyRequestAndCart()
    {
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')
            ->willReturn(null);
        $this->cartProviderMock->expects($this->once())
            ->method('get')
            ->with($this->observerMock)
            ->willReturn(null);
        $this->requestMock->expects($this->never())->method('getPost');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWithRemoveFailedAndFromErrorGrid()
    {
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')
            ->willReturn($this->requestMock);
        $this->cartProviderMock->expects($this->once())
            ->method('get')
            ->with($this->observerMock)
            ->willReturn($this->cartProviderMock);
        $postParams =
            [
                ['sku_remove_failed', true],
                ['from_error_grid', true],
            ];
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->willReturnMap($postParams);
        $this->cartProviderMock->expects($this->once())->method('removeAllAffectedItems');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWithSku()
    {
        $this->observerMock->expects($this->exactly(2))
            ->method('getRequestModel')
            ->willReturn($this->requestMock);
        $this->cartProviderMock->expects($this->exactly(2))
            ->method('get')
            ->with($this->observerMock)
            ->willReturn($this->cartProviderMock);
        $postParams =
            [
                ['sku_remove_failed', false],
                ['remove_sku', false, 'some_sku_123'],
            ];
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->willReturnMap($postParams);
        $this->cartProviderMock->expects($this->once())->method('removeAffectedItem')->with('some_sku_123');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWithoutAddBySkuItems()
    {
        $this->observerMock->expects($this->exactly(2))
            ->method('getRequestModel')
            ->willReturn($this->requestMock);
        $this->cartProviderMock->expects($this->exactly(1))
            ->method('get')
            ->with($this->observerMock)
            ->willReturn($this->cartProviderMock);
        $postParams =
            [
                ['sku_remove_failed', false],
                ['remove_sku', false, null],
                [AbstractSku::LIST_TYPE, [], null],
                ['item', [], []],
            ];
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->willReturnMap($postParams);

        $this->model->execute($this->observerMock);
    }

    public function testExecute()
    {
        $this->observerMock->expects($this->exactly(2))
            ->method('getRequestModel')
            ->willReturn($this->requestMock);
        $this->cartProviderMock->expects($this->exactly(1))
            ->method('get')
            ->with($this->observerMock)
            ->willReturn($this->cartProviderMock);
        $addBySkuItems =
            [
                0 => [
                    'sku' => 'some_sku',
                    'qty' => 11,
                ],
            ];
        $postParams =
            [
                ['sku_remove_failed', false],
                ['remove_sku', false, null],
                [AbstractSku::LIST_TYPE, [], $addBySkuItems],
                ['item', [], []],
            ];
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->willReturnMap($postParams);
        $this->cartProviderMock->expects($this->once())->method('prepareAddProductBySku')->with('some_sku', 11, []);
        $this->observerMock->expects($this->once())
            ->method('getOrderCreateModel')
            ->willReturn($this->orderCreateModelMock);
        $this->cartProviderMock->expects($this->once())
            ->method('saveAffectedProducts')
            ->with($this->orderCreateModelMock, false);

        $storeId = 1;
        $quoteSession = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $quoteSession->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->orderCreateModelMock->expects($this->any())
            ->method('getSession')
            ->willReturn($quoteSession);

        $this->cartMock->expects($this->any())
            ->method('setCurrentStore')
            ->with($storeId)->willReturnSelf();
        $this->requestMock->expects($this->once())->method('setPostValue')->with('item', []);

        $this->model->execute($this->observerMock);
    }
}
