<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Locale\FormatInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory;
use Magento\GiftCard\Controller\Adminhtml\Product\Initialization\Helper\Plugin\GiftCard;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftcardType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftCardTest extends TestCase
{
    /**
     * @var GiftCard
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var GiftcardAmountInterfaceFactory|MockObject
     */
    private $amountFactoryMock;

    /**
     * @var Helper|MockObject
     */
    private $subjectMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $constructorArgs = $this->objectManagerHelper->getConstructArguments(GiftCard::class);
        $attributeRepositoryMock = $constructorArgs['attributeRepository'];
        $attributeMock = $this->getMockBuilder(AttributeInterface::class)
            ->getMockForAbstractClass();
        $attributeRepositoryMock->method('get')
            ->willReturn($attributeMock);
        $attributeMock->method('getAttributeId')
            ->willReturn('attributeId');
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes','setExtensionAttributes', 'getTypeId', 'getData'])
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->amountFactoryMock = $constructorArgs['amountFactory'];

        $this->plugin = $this->objectManagerHelper->getObject(GiftCard::class, $constructorArgs);
    }

    /**
     * @param string $productTypeId
     * @param array $productData
     * @param array $expectedProductData
     * @dataProvider beforeInitializeFromDataDataProvider
     */
    public function testBeforeInitializeFromData(string $productTypeId, array $productData, array $expectedProductData)
    {
        $this->productMock->expects(static::once())
            ->method('getTypeId')
            ->willReturn($productTypeId);

        $this->assertEquals(
            [$this->productMock, $expectedProductData],
            $this->plugin->beforeInitializeFromData($this->subjectMock, $this->productMock, $productData)
        );
    }

    public function beforeInitializeFromDataDataProvider()
    {
        return [
            'gift card, no amount' => [
                GiftcardType::TYPE_GIFTCARD,
                ['initialData'],
                ['initialData', 'giftcard_amounts' => []]
            ],
            'gift card, with amount' => [
                GiftcardType::TYPE_GIFTCARD,
                ['initialData', 'giftcard_amounts' => ['amount data']],
                ['initialData', 'giftcard_amounts' => ['amount data']]
            ],
            'non gift card' => [
                'other product',
                ['initialData'],
                ['initialData']
            ],
        ];
    }

    /**
     * @param string $productTypeId
     * @param array $amountsData
     * @param int $amountFactoryCallNum
     * @param int $callNum
     * @dataProvider afterInitializeDataProvider
     */
    public function testAfterInitialize(
        string $productTypeId,
        array $amountsData,
        int $amountFactoryCallNum,
        int $callNum = 1
    ) {
        $this->productMock->method('getTypeId')
            ->willReturn($productTypeId);
        $this->productMock->method('getData')
            ->willReturn($amountsData);
        $amountMock = $this->getMockForAbstractClass(GiftcardAmountInterface::class);
        $this->amountFactoryMock->expects($this->exactly($amountFactoryCallNum))
            ->method('create')
            ->willReturn($amountMock);
        $extensionMock = $this->getMockBuilder(ProductExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setGiftcardAmounts'])
            ->getMockForAbstractClass();
        $extensionMock->expects($this->exactly($callNum))
            ->method('setGiftcardAmounts');
        $this->productMock->expects($this->exactly($callNum))
            ->method('getExtensionAttributes')
            ->willReturn($extensionMock);
        $this->productMock->expects($this->exactly($callNum))
            ->method('setExtensionAttributes');
        $this->plugin->afterInitialize($this->subjectMock, $this->productMock);
    }

    public function afterInitializeDataProvider()
    {
        $giftcardAmountMockWithDataA = $this->getGiftCardAmountMock();
        $giftcardAmountMockWithDataA->method('getData')->willReturn(['value' => 30]);
        $giftcardAmountMockWithDataE = $this->getGiftCardAmountMock();
        $commaSeparatedValueE = '40,65';
        $floatingPointValueE = 40.65;
        $giftcardAmountMockWithDataE->method('setValue')->willReturn($floatingPointValueE);
        $giftcardAmountMockWithDataE->method('getData')->willReturn(['value' => $commaSeparatedValueE]);
        $this->getLocaleFormatMock()->method('getNumber')
            ->with($giftcardAmountMockWithDataE)
            ->willReturn($floatingPointValueE);
        $commaSeparatedValueF = '10,25';
        $floatingPointValueF = 10.25;
        $giftcardAmountMockWithDataF = $this->getGiftCardAmountMock();
        $giftcardAmountMockWithDataF->method('setValue')->willReturn($floatingPointValueF);
        $giftcardAmountMockWithDataF->method('getData')->willReturn(['value' => $commaSeparatedValueF]);
        $this->getLocaleFormatMock()->method('getNumber')
            ->with($giftcardAmountMockWithDataF)
            ->willReturn($floatingPointValueF);

        return [
            'gift card, no amount' => [
                GiftcardType::TYPE_GIFTCARD,
                [],
                1,
            ],
            'gift card, with amount' => [
                GiftcardType::TYPE_GIFTCARD,
                [['value' => 10]],
                1
            ],
            'non gift card' => [
                'other product',
                ['initialData'],
                0,
                0
            ],
            'two gift cards, with amount, both comma separated' => [
                GiftcardType::TYPE_GIFTCARD,
                [['value' => $giftcardAmountMockWithDataE], ['value' => $giftcardAmountMockWithDataF]],
                2,
                1
            ],
            'two gift cards, with amount, one comma separated' => [
                GiftcardType::TYPE_GIFTCARD,
                [['value' => $giftcardAmountMockWithDataA], ['value' => $giftcardAmountMockWithDataE]],
                2,
                1
            ],
        ];
    }

    /**
     * Get GiftCardAmountInterface mock object
     *
     * @return GiftcardAmountInterface
     */
    private function getGiftCardAmountMock(): GiftcardAmountInterface
    {
        return $this->getMockBuilder(GiftcardAmountInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setData', 'setValue', 'unsetData'])
            ->getMockForAbstractClass();
    }

    /**
     * Get FormatInterface mock object
     *
     * @return FormatInterface
     */
    private function getLocaleFormatMock(): FormatInterface
    {
        return $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNumber'])
            ->getMockForAbstractClass();
    }
}
