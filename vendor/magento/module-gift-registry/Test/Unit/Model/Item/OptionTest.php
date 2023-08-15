<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Item;

use Magento\Catalog\Model\Product;
use Magento\GiftRegistry\Model\Item\Option;
use PHPUnit\Framework\TestCase;

class OptionTest extends TestCase
{
    /**
     * @param mixed $product
     * @param mixed $expectedProduct
     * @param int $expectedProductId
     * @dataProvider setProductDataProvider
     */
    public function testSetProduct($product, $expectedProduct, $expectedProductId)
    {
        $model = $this->createPartialMock(Option::class, ['getValue']);
        $model->setProduct($product);

        $this->assertEquals($expectedProduct, $model->getProduct());
        $this->assertEquals($expectedProductId, $model->getProductId());
    }

    public function setProductDataProvider()
    {
        $product = $this->createPartialMock(Product::class, ['getId', '__sleep']);
        $product->expects($this->any())->method('getId')->willReturn(3);
        return [[$product, $product, 3], [null, null, null]];
    }
}
