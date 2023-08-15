<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Price;
use Magento\GiftCard\Model\Product\CatalogPrice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogPriceTest extends TestCase
{
    /**
     * @var CatalogPrice
     */
    protected $catalogPrice;

    /**
     * @var MockObject
     */
    protected $productMock;

    protected function setUp(): void
    {
        $this->productMock = $this->createMock(Product::class);
        $this->catalogPrice = new CatalogPrice();
    }

    public function testGetCatalogPrice()
    {
        $priceModelMock = $this->getMockBuilder(Price::class)
            ->addMethods(['getMinAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock->expects($this->once())->method('getPriceModel')->willReturn($priceModelMock);
        $priceModelMock->expects(
            $this->once()
        )->method(
            'getMinAmount'
        )->with(
            $this->productMock
        )->willReturn(
            15
        );
        $this->assertEquals(15, $this->catalogPrice->getCatalogPrice($this->productMock));
    }

    public function testGetCatalogRegularPrice()
    {
        $this->assertNull($this->catalogPrice->getCatalogRegularPrice($this->productMock));
    }
}
