<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Pricing\Render;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolverInterface;
use Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface;
use Magento\Catalog\Pricing\Price\SpecialPrice;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\GiftCard\Pricing\Render\FinalPriceBox;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FinalPriceBoxTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var SpecialPrice|MockObject
     */
    protected $saleableItemMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    protected $priceCurrencyMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var SalableResolverInterface
     */
    private $salableResolver;

    /**
     * @var MinimalPriceCalculatorInterface
     */
    private $minimalPriceCalculator;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->saleableItemMock = $this->getMockBuilder(Product::class)
            ->addMethods(
                [
                    'getGiftcardAmounts',
                    'getAllowOpenAmount',
                    'getOpenAmountMin',
                    'getOpenAmountMax',
                    'hasPreconfiguredValues'
                ]
            )
            ->onlyMethods(['hasCustomOptions', 'getCustomOption', 'getPreconfiguredValues'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceCurrencyMock = $this->getMockBuilder(
            PriceCurrencyInterface::class
        )->disableOriginalConstructor()
            ->setMethods(['convertAndFormat', 'convert'])->getMockForAbstractClass();

        $this->contextMock = $this->createPartialMock(
            Context::class,
            ['getStoreManager']
        );

        $this->storeManagerMock = $this->getMockBuilder(
            StoreManagerInterface::class
        )->disableOriginalConstructor()
            ->setMethods(['getStore'])->getMockForAbstractClass();

        $this->storeMock = $this->createPartialMock(
            Store::class,
            ['getCurrentCurrencyCode']
        );

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->contextMock->expects($this->any())
            ->method('getStoreManager')
            ->willReturn($this->storeManagerMock);

        $this->priceCurrencyMock = $this->getMockBuilder(
            PriceCurrencyInterface::class
        )->disableOriginalConstructor()
            ->setMethods(['convertAndFormat', 'convert'])->getMockForAbstractClass();

        $this->salableResolver = $this->getMockBuilder(SalableResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->minimalPriceCalculator = $this->getMockBuilder(MinimalPriceCalculatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @param array $amounts
     * @param bool $isOpenAmount
     * @param bool $expected
     *
     * @dataProvider isRegularPriceDataProvider
     */
    public function testIsRegularPrice($amounts, $isOpenAmount, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $this->saleableItemMock->expects($this->any())
            ->method('getAllowOpenAmount')
            ->willReturn($isOpenAmount);

        $finalPriceBox = $this->getFinalPriceBox();

        $this->assertEquals($expected, $finalPriceBox->isRegularPrice());
    }

    /**
     * @return array
     */
    public function isRegularPriceDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                ],
                'isOpenAmount' => false,
                'expected' => true,
            ],
            'two_amount' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'isOpenAmount' => false,
                'expected' => false,
            ],
            'open_amount' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                ],
                'isOpenAmount' => true,
                'expected' => false,
            ]

        ];
    }

    /**
     * @return FinalPriceBox
     */
    protected function getFinalPriceBox()
    {
        return $this->objectManager->getObject(
            FinalPriceBox::class,
            [
                'saleableItem' => $this->saleableItemMock,
                'priceCurrency' => $this->priceCurrencyMock,
                'context' => $this->contextMock,
                'salableResolver' => $this->salableResolver,
                'minimalPriceCalculator' => $this->minimalPriceCalculator
            ]
        );
    }

    /**
     * @param bool $isOpenAmount
     * @param bool $expected
     *
     * @dataProvider isOpenAmountDataProvider
     */
    public function testIsOpenAmountAvailable($isOpenAmount, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $this->saleableItemMock->expects($this->any())
            ->method('getAllowOpenAmount')
            ->willReturn($isOpenAmount);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->isOpenAmountAvailable());
    }

    /**
     * @return array
     */
    public function isOpenAmountDataProvider()
    {
        return [
            'major' => [
                'isOpenAmount' => true,
                'expected' => true,
            ],
            'minor' => [
                'isOpenAmount' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * @param array $amounts
     * @param float $expected
     *
     * @dataProvider getRegularPriceDataProvider
     */
    public function testGetRegularPrice($amounts, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->getRegularPrice());
    }

    /**
     * @return array
     */
    public function getRegularPriceDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 20.,
                    ],
                ],
                'expected' => 20.,
            ],
            'two_amount' => [
                'amounts' => [
                    [
                        'website_value' => 20.,
                    ],
                    [
                        'website_value' => 30.
                    ],
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @param array $amounts
     * @param float $expected
     *
     * @dataProvider getAmountsDataProvider
     */
    public function testGetAmounts($amounts, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->getAmounts());
    }

    /**
     * @return array
     */
    public function getAmountsDataProvider()
    {
        return [
            'zero_amount' => [
                'amounts' => [],
                'expected' => [],
            ],
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 50.,
                    ],
                ],
                'expected' => [50.],
            ],
            'two_amount' => [
                'amounts' => [
                    [
                        'website_value' => 60.,
                    ],
                    [
                        'website_value' => 70.
                    ],
                ],
                'expected' => [60., 70.],
            ],
        ];
    }

    public function testConvertAndFormatCurrency()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $this->priceCurrencyMock->expects($this->any())
            ->method('convertAndFormat')
            ->with(10, true)
            ->willReturn('$10.00');

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals('$10.00', $finalPriceBox->convertAndFormatCurrency(10, true));
    }

    public function testConvertCurrency()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $this->priceCurrencyMock->expects($this->any())
            ->method('convert')
            ->with(20)
            ->willReturn('50.00');

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals('50.00', $finalPriceBox->convertCurrency(20));
    }

    /**
     * @param array $amounts
     * @param bool $expected
     *
     * @dataProvider isAmountAvailableDataProvider
     */
    public function testIsAmountAvailable($amounts, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->isAmountAvailable());
    }

    /**
     * @return array
     */
    public function isAmountAvailableDataProvider()
    {
        return [
            'zero_amount' => [
                'amounts' => [],
                'expected' => false,
            ],
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 50.,
                    ],
                ],
                'expected' => true,
            ]
        ];
    }

    public function testGetOpenAmountMin()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMin')
            ->willReturn(0.);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals(0., $finalPriceBox->getOpenAmountMin());
    }

    public function testGetOpenAmountMax()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMax')
            ->willReturn(20.);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals(20., $finalPriceBox->getOpenAmountMax());
    }

    public function testGetCurrentCurrency()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $this->storeMock->expects($this->any())
            ->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals('USD', $finalPriceBox->getCurrentCurrency());
    }

    /**
     * @param array $amounts
     * @param float $openMinAmount
     * @param float $openMaxAmount
     * @param bool $isOpenAmount
     * @param float $expected
     *
     * @dataProvider getMinValueDataProvider
     */
    public function testGetMinValue($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount, $expected)
    {
        $this->preparePriceCalculation($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount);
        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->getMinValue());
    }

    /**
     * @param array $amounts
     * @param float $openMinAmount
     * @param float $openMaxAmount
     * @param bool $isOpenAmount
     */
    protected function preparePriceCalculation($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($amounts);

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMin')
            ->willReturn($openMinAmount);

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMax')
            ->willReturn($openMaxAmount);

        $this->saleableItemMock->expects($this->any())
            ->method('getAllowOpenAmount')
            ->willReturn($isOpenAmount);
    }

    /**
     * @return array
     */
    public function getMinValueDataProvider()
    {
        return [
            'open_amount_minimal' => [
                'amounts' => [
                    [
                        'website_value' => 30.,
                    ],
                    [
                        'website_value' => 60.
                    ],
                ],
                'openMinAmount' => 20.,
                'openMaxAmount' => 90.,
                'isOpenAmount' => true,
                'expected' => 20.,
            ],
            'amounts_minimal' => [
                'amounts' => [
                    [
                        'website_value' => 100.,
                    ],
                    [
                        'website_value' => 90.
                    ],
                ],
                'openMinAmount' => 110.,
                'openMaxAmount' => 120.,
                'isOpenAmount' => true,
                'expected' => 90.,
            ],
            'open_amounts_disabled' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'openMinAmount' => 1.,
                'openMaxAmount' => 2.,
                'isOpenAmount' => false,
                'expected' => 10.,
            ]
        ];
    }

    /**
     * @param array $amounts
     * @param float $openMinAmount
     * @param float $openMaxAmount
     * @param bool $isOpenAmount
     * @param float $expected
     *
     * @dataProvider isMinEqualToMaxDataProvider
     */
    public function testIsMinEqualToMax($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount, $expected)
    {
        $this->preparePriceCalculation($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount);
        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->isMinEqualToMax());
    }

    /**
     * @return array
     */
    public function isMinEqualToMaxDataProvider()
    {
        return [
            'equal_open_and_amounts' => [
                'amounts' => [
                    [
                        'website_value' => 20.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'openMinAmount' => 20.,
                'openMaxAmount' => 20.,
                'isOpenAmount' => true,
                'expected' => true,
            ],
            'non_equal_open_and_amounts' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'openMinAmount' => 10.,
                'openMaxAmount' => 20.,
                'isOpenAmount' => true,
                'expected' => false,
            ],
            'open_amounts_disabled' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                ],
                'openMinAmount' => 20.,
                'openMaxAmount' => 30.,
                'isOpenAmount' => false,
                'expected' => true,
            ]
        ];
    }

    /**
     * @param $value
     * @param $result
     *
     * @dataProvider dataProviderOptionValue
     */
    public function testGetGiftcardAmount($value, $result)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn([]);

        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->any())
            ->method('getData')
            ->with('giftcard_amount')
            ->willReturn($value);

        $this->saleableItemMock->expects($this->once())
            ->method('hasPreconfiguredValues')
            ->willReturn(true);
        $this->saleableItemMock->expects($this->once())
            ->method('getPreconfiguredValues')
            ->willReturn($option);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($result, $finalPriceBox->getGiftcardAmount());
    }

    /**
     * @return array
     */
    public function dataProviderOptionValue()
    {
        return [
            [0., 0.],
            [1., 1.],
        ];
    }
}
