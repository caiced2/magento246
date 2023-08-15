<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Controller;

use Magento\AdvancedCheckout\Controller\Cart;
use Magento\Catalog\Controller\Product\View\ViewInterface;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function testControllerImplementsProductViewInterface()
    {
        $this->assertInstanceOf(
            ViewInterface::class,
            $this->createMock(Cart::class)
        );
    }
}
