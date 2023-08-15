<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\Backend;

use Magento\AdvancedCheckout\Model\Backend\Cart;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\AdvancedCheckout\Model\Backend\Cart
 */
class CartTest extends TestCase
{
    public function testGetActualQuote()
    {
        $helper = new ObjectManager($this);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getQuote')->willReturn('some value');
        /** @var Cart $model */
        $model = $helper->getObject(Cart::class);
        $model->setQuote($quote);
        $this->assertEquals('some value', $model->getActualQuote());
    }
}
