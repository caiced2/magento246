<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Observer\AddPaymentCustomerBalanceItemObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use PHPUnit\Framework\TestCase;

class AddPaymentCustomerBalanceItemObserverTest extends TestCase
{
    /**
     * @var DataObject
     */
    private $event;

    /** @var AddPaymentCustomerBalanceItemObserver */
    private $model;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var SalesModelInterface
     */
    private $salesModel;

    protected function setUp(): void
    {
        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            AddPaymentCustomerBalanceItemObserver::class
        );
        $this->salesModel = $this->getMockForAbstractClass(SalesModelInterface::class);
    }

    /**
     * @param float|string $amount
     * @dataProvider addPaymentCustomerBalanceItemDataProviderSuccess
     */
    public function testAddPaymentCustomerBalanceItemSuccess($amount)
    {
        $this->salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('customer_balance_base_amount')
            ->willReturn($amount);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->willReturn($this->salesModel);
        $cart->expects($this->once())->method('addDiscount')->with(abs((float)$amount));

        $this->event->setCart($cart);
        $this->model->execute($this->observer);
    }

    /**
     * @param float|string $amount
     * @dataProvider addPaymentCustomerBalanceItemDataProviderFail
     */
    public function testAddPaymentCustomerBalanceItemFail($amount)
    {
        $this->salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('customer_balance_base_amount')
            ->willReturn($amount);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->willReturn($this->salesModel);
        $cart->expects($this->never())->method('addDiscount');

        $this->event->setCart($cart);
        $this->model->execute($this->observer);
    }

    /**
     * @return array
     */
    public function addPaymentCustomerBalanceItemDataProviderSuccess()
    {
        return [[0.1], [-0.1], ['0.1']];
    }

    /**
     * @return array
     */
    public function addPaymentCustomerBalanceItemDataProviderFail()
    {
        return [[0.0], [''], [' '], [null]];
    }
}
