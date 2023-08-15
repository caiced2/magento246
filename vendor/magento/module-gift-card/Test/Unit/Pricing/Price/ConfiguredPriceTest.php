<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Catalog\Pricing\Price\BasePrice;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use Magento\GiftCard\Pricing\Price\ConfiguredPrice;
use Magento\Wishlist\Model\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfiguredPriceTest extends TestCase
{
    /**
     * @var Product|MockObject
     */
    protected $saleableItem;

    /**
     * @var CalculatorInterface|MockObject
     */
    protected $calculator;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    protected $priceCurrency;

    /**
     * @var ItemInterface|MockObject
     */
    protected $item;

    /**
     * @var ConfiguredPrice|MockObject
     */
    protected $model;

    /**
     * @var PriceInfoInterface|MockObject
     */
    protected $priceInfo;

    protected function setUp(): void
    {
        $this->priceInfo = $this->getMockBuilder(PriceInfoInterface::class)
            ->getMock();

        $this->saleableItem = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPrice',
                'hasCustomOptions',
                'getCustomOption',
                'getPriceInfo',
            ])
            ->getMock();
        $this->saleableItem->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($this->priceInfo);

        $this->calculator = $this->getMockBuilder(CalculatorInterface::class)
            ->getMock();

        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->getMock();

        $this->item = $this->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->model = new ConfiguredPrice(
            $this->saleableItem,
            null,
            $this->calculator,
            $this->priceCurrency,
            $this->item
        );
    }

    /**
     * @param $productPriceValue
     * @param $optionValue
     * @param $basePriceValue
     * @param $result
     *
     * @dataProvider dataProviderForGetValue
     */
    public function testGetValue($productPriceValue, $optionValue, $basePriceValue, $result)
    {
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->any())
            ->method('getValue')
            ->willReturn($optionValue);

        $price = $this->getMockBuilder(PriceInterface::class)
            ->getMock();
        $price->expects($this->once())
            ->method('getValue')
            ->willReturn($basePriceValue);

        $this->priceInfo->expects($this->once())
            ->method('getPrice')
            ->with(BasePrice::PRICE_CODE)
            ->willReturn($price);

        $this->saleableItem->expects($this->once())
            ->method('getPrice')
            ->willReturn($productPriceValue);
        $this->saleableItem->expects($this->once())
            ->method('hasCustomOptions')
            ->willReturn(true);
        $this->saleableItem->expects($this->once())
            ->method('getCustomOption')
            ->with('giftcard_amount')
            ->willReturn($option);

        $this->item->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->saleableItem);
        $this->item->expects($this->once())
            ->method('getOptionByCode')
            ->with('option_ids')
            ->willReturn([]);

        $this->assertEquals($result, $this->model->getValue());
    }

    /**
     * @return array
     */
    public function dataProviderForGetValue()
    {
        return [
            [0., 0., 0., 0.],
            [1., 2., 3., 2.],
            [2., 3., 4., 3.],
        ];
    }
}
