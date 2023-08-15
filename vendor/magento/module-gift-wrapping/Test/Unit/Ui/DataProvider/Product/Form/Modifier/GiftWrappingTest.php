<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use Magento\Catalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier\AbstractModifierTest;
use Magento\GiftWrapping\Helper\Data as GiftWrappingData;
use Magento\GiftWrapping\Ui\DataProvider\Product\Form\Modifier\GiftWrapping;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @method GiftWrapping getModel
 */
class GiftWrappingTest extends AbstractModifierTest
{
    /**
     * @var GiftWrappingData|MockObject
     */
    protected $giftWrappingDataMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->giftWrappingDataMock = $this->getMockBuilder(GiftWrappingData::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createModel()
    {
        return $this->objectManager->getObject(GiftWrapping::class, [
            'locator' => $this->locatorMock,
            'arrayManager' => $this->arrayManagerMock,
            'data' => $this->giftWrappingDataMock,
        ]);
    }

    public function testModifyMeta()
    {
        $this->assertNotEmpty($this->getModel()->modifyMeta([
            'test_group_code' => [
                'fields' => [
                    GiftWrapping::FIELD_GIFT_WRAPPING_PRICE => 6,
                ],
            ]
        ]));
    }

    public function testModifyData()
    {
        $this->assertNotEmpty($this->getModel()->modifyData([
            1 => [
                GiftWrapping::DATA_SOURCE_DEFAULT => [
                    GiftWrapping::FIELD_GIFT_WRAPPING_PRICE => 56,
                ],
            ],
        ]));
    }

    public function testModifyDataWithDefaultGiftWrappingPrice()
    {
        $productId = 1;
        $this->productMock->expects($this->any())->method('getId')->willReturn($productId);

        $configValue = 1;
        $this->giftWrappingDataMock->expects($this->any())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn($configValue);

        $data = [$productId => [
            GiftWrapping::DATA_SOURCE_DEFAULT => [
                GiftWrapping::FIELD_GIFT_WRAPPING_AVAILABLE => Boolean::VALUE_USE_CONFIG,
            ],
        ]];
        $expectedResult = [$productId => [
            GiftWrapping::DATA_SOURCE_DEFAULT => [
                GiftWrapping::FIELD_GIFT_WRAPPING_AVAILABLE => $configValue,
                'use_config_gift_wrapping_available' => 1
            ],
        ]];

        $this->assertEquals($expectedResult, $this->getModel()->modifyData($data));
    }

    public function testModifyDataUsesConfigurationValuesForNewProduct()
    {
        $productId = null;
        $configValue = 1;
        $this->giftWrappingDataMock->expects($this->any())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn($configValue);

        $expectedResult = [$productId => [
            GiftWrapping::DATA_SOURCE_DEFAULT => [
                GiftWrapping::FIELD_GIFT_WRAPPING_AVAILABLE => $configValue,
                'use_config_gift_wrapping_available' => 1
            ],
        ]];

        $this->assertEquals($expectedResult, $this->getModel()->modifyData([]));
    }
}
