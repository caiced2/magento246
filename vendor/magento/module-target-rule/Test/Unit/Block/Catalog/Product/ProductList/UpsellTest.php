<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Block\Catalog\Product\ProductList;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Block\Catalog\Product\ProductList\Upsell;
use PHPUnit\Framework\TestCase;

class UpsellTest extends TestCase
{
    /**
     * @var Upsell
     */
    protected $block;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Cart
     */
    protected $cart;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->registry = $this->createPartialMock(Registry::class, ['registry']);
        $this->cart = $this->createPartialMock(Cart::class, ['getProductIds']);
        $this->block = $objectManager->getObject(
            Upsell::class,
            [
                'cart' => $this->cart,
                'registry' => $this->registry
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->block = null;
    }

    /**
     * test for getExcludeProductIds
     */
    public function testGetExcludeProductIds()
    {
        $productMock = $this->createPartialMock(Product::class, ['getEntityId', '__wakeup']);
        $this->registry->expects($this->once())
            ->method('registry')
            ->willReturn($productMock);
        $this->cart->expects($this->once())
            ->method('getProductIds')
            ->willReturn(['1', '2', '4']);
        $productMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn('6');

        $this->assertEquals([1, 2, 4, 6], $this->block->getExcludeProductIds());
    }
}
