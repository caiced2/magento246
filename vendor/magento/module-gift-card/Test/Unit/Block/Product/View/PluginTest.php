<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Block\Product\View;

use Magento\Catalog\Block\Product\View;
use Magento\GiftCard\Block\Product\View\Plugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * Covered afterGetWishlistOptions
     *
     * @test
     */
    public function testAfterGetWishlistOptions()
    {
        $expected = ['key1' => 'value1', 'giftcardInfo' => '[id^=giftcard]'];
        $param = ['key1' => 'value1'];
        $block = $this->getMockBuilder(
            View::class
        )->disableOriginalConstructor()
            ->getMock();
        /** @var View $block */
        $this->assertEquals(
            $expected,
            (new Plugin())->afterGetWishlistOptions($block, $param)
        );
    }
}
