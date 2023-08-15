<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Cart\SalesModel;

use Magento\CustomerBalance\Model\Cart\SalesModel\Quote;
use PHPUnit\Framework\MockObject\MockObject;

class QuoteTest extends \Magento\Payment\Test\Unit\Model\Cart\SalesModel\QuoteTest
{
    /** @var Quote */
    protected $_model;

    /** @var \Magento\Quote\Model\Quote|MockObject */
    protected $_quoteMock;

    protected function setUp(): void
    {
        $this->_quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->_model = new Quote($this->_quoteMock);
    }

    public function testGetDataUsingMethod()
    {
        $this->_quoteMock->expects(
            $this->exactly(2)
        )->method(
            'getDataUsingMethod'
        )->with(
            $this->anything(),
            'any args'
        )->willReturnCallback(
            function ($key) {
                return $key == 'base_customer_bal_amount_used' ? 'customer_balance_amount result' : 'some value';
            }
        );
        $this->assertEquals('some value', $this->_model->getDataUsingMethod('any key', 'any args'));
        $this->assertEquals(
            'customer_balance_amount result',
            $this->_model->getDataUsingMethod('customer_balance_base_amount', 'any args')
        );
    }
}
