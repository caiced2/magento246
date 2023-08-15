<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Product\CartConfiguration\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CartConfiguration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Model\Product\CartConfiguration\Plugin\GiftCard;
use PHPUnit\Framework\TestCase;

class GiftCardTest extends TestCase
{
    /**
     * @var GiftCard
     */
    protected $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            GiftCard::class
        );
    }

    /**
     * @param $productType
     * @param $expected
     * @dataProvider aroundIsProductConfiguredDataProvider
     */
    public function testAroundIsProductConfigured($productType, $expected)
    {
        $config = ['giftcard_amount' => true];

        $subject = $this->getMockBuilder(CartConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $proceed = function (Product $productParam, array $configParam) use ($product, $config) {
            $this->assertEquals($productParam, $product);
            $this->assertEquals($configParam, $config);
            return false;
        };

        $product->expects($this->once())
            ->method('getTypeId')
            ->willReturn($productType);

        $this->assertEquals($expected, $this->model->aroundIsProductConfigured($subject, $proceed, $product, $config));
    }

    /**
     * @return array
     */
    public function aroundIsProductConfiguredDataProvider()
    {
        return [
            ['giftcard', true],
            ['simple', false]
        ];
    }
}
