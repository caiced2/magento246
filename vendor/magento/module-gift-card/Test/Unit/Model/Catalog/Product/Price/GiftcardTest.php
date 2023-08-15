<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Catalog\Product\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\Option;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Model\Catalog\Product\Price\Giftcard;
use PHPUnit\Framework\TestCase;

class GiftcardTest extends TestCase
{
    /**
     * @var Giftcard
     */
    protected $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(Giftcard::class);
    }

    /**
     * @param array $amounts
     * @param bool $withCustomOptions
     * @param float $expectedPrice
     *
     * @return void
     * @dataProvider getPriceDataProvider
     */
    public function testGetPrice(array $amounts, bool $withCustomOptions, float $expectedPrice): void
    {
        $product = $this->getMockBuilder(Product::class)
            ->addMethods(['getAllowOpenAmount'])
            ->onlyMethods(['getData', 'hasCustomOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getAllowOpenAmount')->willReturn(false);
        $product->expects($this->any())->method('hasCustomOptions')->willReturn($withCustomOptions);
        $product->expects($this->atLeastOnce())->method('getData')->willReturnMap(
            [['price', null, null], ['giftcard_amounts', null, $amounts]]
        );

        $this->assertEquals($expectedPrice, $this->model->getPrice($product));
    }

    /**
     * @return array
     */
    public function getPriceDataProvider(): array
    {
        return [
            [[['website_id' => 0, 'value' => '10.0000', 'website_value' => 10]], false, 10],
            [[['website_id' => 0, 'value' => '10.0000', 'website_value' => 10]], true, 0],
            [
                [
                    ['website_id' => 0, 'value' => '10.0000', 'website_value' => 10],
                    ['website_id' => 0, 'value' => '100.0000', 'website_value' => 100]
                ],
                false,
                0
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetPriceWithFixedAmount(): void
    {
        $price = 3;

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->exactly(2))->method('getData')->with('price')->willReturn($price);

        $this->assertEquals($price, $this->model->getPrice($product));
    }

    /**
     * @return void
     */
    public function testGetFinalPrice(): void
    {
        $productPrice = 5;
        $optionPrice = 3;

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customOption = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->once())->method('getPrice')->willReturn($productPrice);
        $product->expects($this->once())->method('hasCustomOptions')->willReturn(true);
        $customOption->expects($this->once())->method('getValue')->willReturn($optionPrice);
        $product
            ->method('getCustomOption')
            ->withConsecutive(['giftcard_amount'], ['option_ids'])
            ->willReturnOnConsecutiveCalls($customOption, null);
        $product->expects($this->once())
            ->method('setData')
            ->with('final_price', $productPrice + $optionPrice)->willReturnSelf();
        $product->expects($this->once())
            ->method('getData')
            ->with('final_price')
            ->willReturn($productPrice + $optionPrice);

        $this->assertEquals($productPrice + $optionPrice, $this->model->getFinalPrice(5, $product));
    }
}
