<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Plugin\Checkout\CustomerData;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Plugin\Checkout\CustomerData\Cart as CartPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    /**
     * @var CartPlugin
     */
    private $cartPlugin;

    /**
     * @var MockObject|Cart
     */
    private $advancedCartMock;

    protected function setUp(): void
    {
        $this->advancedCartMock = $this->createMock(\Magento\AdvancedCheckout\Model\Cart::class);
        $this->cartPlugin = new CartPlugin(
            $this->advancedCartMock
        );
    }

    /**
     * @param array $failedItems
     * @param string $expectedMessage
     * @dataProvider dataProvider
     */
    public function testAfterGetSectionDataSetsMessageIfCartHasItemsThatRequireAttention($failedItems, $expectedMessage)
    {
        $cartMock = $this->createMock(\Magento\Checkout\CustomerData\Cart::class);
        $this->advancedCartMock->expects($this->any())->method('getFailedItems')->willReturn($failedItems);
        $result = $this->cartPlugin->afterGetSectionData($cartMock, []);
        $stringResults = array_map(function ($message) {
            return (string)$message;
        }, $result);
        $this->assertContains($expectedMessage, $stringResults);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [[], ''],
            [['product_sku'], '1 item(s) need your attention.'],
        ];
    }
}
