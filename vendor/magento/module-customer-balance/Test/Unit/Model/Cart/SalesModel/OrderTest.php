<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Cart\SalesModel;

use Magento\CustomerBalance\Model\Cart\SalesModel\Order;
use PHPUnit\Framework\MockObject\MockObject;

class OrderTest extends \Magento\Payment\Test\Unit\Model\Cart\SalesModel\OrderTest
{
    /** @var Order */
    protected $_model;

    /** @var \Magento\Sales\Model\Order|MockObject */
    protected $_orderMock;

    protected function setUp(): void
    {
        $this->_orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->_model = new Order($this->_orderMock);
    }

    public function testGetDataUsingMethod()
    {
        $this->_orderMock->expects(
            $this->exactly(2)
        )->method(
            'getDataUsingMethod'
        )->with(
            $this->anything(),
            'any args'
        )->willReturnCallback(
            function ($key) {
                return $key == 'base_customer_balance_amount' ? 'customer_balance result' : 'some value';
            }
        );
        $this->assertEquals('some value', $this->_model->getDataUsingMethod('any key', 'any args'));
        $this->assertEquals(
            'customer_balance result',
            $this->_model->getDataUsingMethod('customer_balance_base_amount', 'any args')
        );
    }
}
