<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use Magento\Reward\Observer\AddPaymentRewardItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddPaymentRewardItemTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $eventMock;

    /**
     * @var AddPaymentRewardItem
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var SalesModelInterface
     */
    private $salesModel;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->model = $this->objectManagerHelper->getObject(
            AddPaymentRewardItem::class
        );
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCart', 'getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this->createMock(Observer::class);
        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->salesModel = $this->getMockForAbstractClass(SalesModelInterface::class);
    }

    /**
     * @param float|string $amount
     * @dataProvider addPaymentRewardItemDataProviderSuccess
     */
    public function testAddPaymentRewardItemSuccess($amount)
    {
        $this->salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('base_reward_currency_amount')
            ->willReturn($amount);
        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->willReturn($this->salesModel);
        $cart->expects($this->once())->method('addDiscount')->with(abs((float)$amount));
        $this->eventMock->expects($this->once())->method('getCart')->willReturn($cart);
        $this->model->execute($this->observerMock);
    }

    /**
     * @param float|string $amount
     * @dataProvider addPaymentRewardItemDataProviderFail
     */
    public function testAddPaymentRewardItemFail($amount)
    {
        $this->salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('base_reward_currency_amount')
            ->willReturn($amount);
        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->willReturn($this->salesModel);
        $cart->expects($this->never())->method('addDiscount');
        $this->eventMock->expects($this->once())->method('getCart')->willReturn($cart);
        $this->model->execute($this->observerMock);
    }

    /**
     * @return array
     */
    public function addPaymentRewardItemDataProviderSuccess()
    {
        return [[0.1], [-0.1], ['0.1']];
    }

    /**
     * @return array
     */
    public function addPaymentRewardItemDataProviderFail()
    {
        return [[0.0], [''], [' '], [null]];
    }
}
