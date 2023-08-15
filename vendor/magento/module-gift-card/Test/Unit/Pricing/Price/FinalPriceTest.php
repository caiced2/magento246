<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\BasePrice;
use Magento\Catalog\Pricing\Price\SpecialPrice;
use Magento\Framework\Pricing\Adjustment\Calculator;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\GiftCard\Pricing\Price\FinalPrice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FinalPriceTest extends TestCase
{
    /**
     * @var FinalPrice
     */
    protected $model;

    /**
     * @var BasePrice|MockObject
     */
    protected $basePriceMock;

    /**
     * @var SaleableInterface|MockObject
     */
    protected $saleableMock;

    /**
     * @var Calculator|MockObject
     */
    protected $calculatorMock;

    /**
     * @var SpecialPrice|MockObject
     */
    protected $saleableItemMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    protected $priceCurrencyMock;

    /**
     * Set up function
     */
    protected function setUp(): void
    {
        $this->saleableMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftcardAmounts'])
            ->onlyMethods(['getPriceInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->basePriceMock = $this->createMock(BasePrice::class);

        $this->calculatorMock = $this->createMock(Calculator::class);

        $this->priceCurrencyMock = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $this->priceCurrencyMock->expects($this->any())
            ->method('convertAndRound')
            ->willReturnCallback(
                function ($arg) {
                    return round(0.5 * $arg, 2);
                }
            );

        $this->model = new FinalPrice(
            $this->saleableMock,
            1,
            $this->calculatorMock,
            $this->priceCurrencyMock
        );
    }

    /**
     * @param array $amounts
     * @param bool $expected
     *
     * @dataProvider getAmountsDataProvider
     */
    public function testGetAmounts($amounts, $expected)
    {
        $this->saleableMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $this->assertEquals($expected, $this->model->getAmounts());
    }

    /**
     * @return array
     */
    public function getAmountsDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                ],
                'expected' => [5.],
            ],
            'two_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                    ['website_value' => 20.],
                ],
                'expected' => [5., 10.],
            ],
            'zero_amount' => [
                'amounts' => [],
                'expected' => [],
            ]

        ];
    }

    public function testGetAmountsCached()
    {
        $amount = [['website_value' => 5]];

        $this->saleableMock->expects($this->once())
            ->method('getGiftcardAmounts')
            ->willReturn($amount);

        $this->model->getAmounts();

        $this->assertEquals([2.5], $this->model->getAmounts());
    }

    /**
     * @param array $amounts
     * @param bool $expected
     *
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($amounts, $expected)
    {
        $this->saleableMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $this->assertEquals($expected, $this->model->getValue());
    }

    /**
     * @return array
     */
    public function getValueDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                ],
                'expected' => 5.,
            ],
            'two_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                    ['website_value' => 20.],
                ],
                'expected' => 5.,
            ],
            'zero_amount' => [
                'amounts' => [],
                'expected' => false,
            ]

        ];
    }
}
