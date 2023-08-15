<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Plugin\Catalog\Model\Product\Attribute\Backend;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Backend\Price;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\FormatInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardType;
use Magento\GiftCard\Plugin\Catalog\Model\Product\Attribute\Backend\Price as BackendPricePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gift Card Open Amount validation.
 */
class PriceTest extends TestCase
{
    /**
     * @var Price|MockObject
     */
    private $subjectMock;

    /**
     * @var Product|MockObject
     */
    private $entityMock;

    /**
     * @var FormatInterface|MockObject
     */
    private $localeFormatMock;

    /**
     * @var BackendPricePlugin
     */
    private $priceBackend;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Price::class);
        $this->entityMock = $this->getMockBuilder(Product::class)
            ->setMethods(['getAllowOpenAmount', 'getOpenAmountMin', 'getOpenAmountMax', 'getTypeId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeFormatMock = $this->getMockForAbstractClass(FormatInterface::class);
        $this->priceBackend = new BackendPricePlugin($this->localeFormatMock);
    }

    /**
     * Validate Product with disabled Open Amount or wrong type.
     *
     * @param string $typeId
     * @return void
     * @dataProvider validateProductWithDisabledOpenAmountDataProvider
     */
    public function testValidateProductWithOpenAmountDisabled(string $typeId): void
    {
        $this->entityMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn($typeId);

        $this->entityMock->expects($typeId === GiftCardType::TYPE_GIFTCARD ? $this->once() : $this->never())
            ->method('getAllowOpenAmount')
            ->willReturn(false);

        $this->entityMock->expects($this->never())
            ->method('getOpenAmountMin');

        $this->entityMock->expects($this->never())
            ->method('getOpenAmountMax');

        $this->localeFormatMock->expects($this->never())
            ->method('getNumber');

        $this->priceBackend->beforeValidate($this->subjectMock, $this->entityMock);
    }

    /**
     * DataProvider for testValidateProductWithDisabledOpenAmount().
     *
     * @return array
     */
    public function validateProductWithDisabledOpenAmountDataProvider(): array
    {
        return [
            [ProductType::DEFAULT_TYPE],
            [GiftCardType::TYPE_GIFTCARD],
        ];
    }

    /**
     * Validate Product with correct Open Amount range.
     *
     * @param string $openAmountMin
     * @param string $openAmountMax
     * @return void
     * @dataProvider validateProductWithCorrectAmountsDataProvider
     */
    public function testValidateProductWithCorrectAmounts(string $openAmountMin, string $openAmountMax): void
    {
        $this->entityMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(GiftCardType::TYPE_GIFTCARD);

        $this->entityMock->expects($this->once())
            ->method('getAllowOpenAmount')
            ->willReturn(true);

        $this->entityMock->expects($this->once())
            ->method('getOpenAmountMin')
            ->willReturn($openAmountMin);

        $this->entityMock->expects($this->once())
            ->method('getOpenAmountMax')
            ->willReturn($openAmountMax);

        $this->localeFormatMock->expects($this->exactly(2))
            ->method('getNumber')
            ->willReturnCallback(
                function ($number) {
                    return str_replace(',', '.', $number);
                }
            );

        $this->priceBackend->beforeValidate($this->subjectMock, $this->entityMock);
    }

    /**
     * DataProvider for testValidateProductWithCorrectAmounts().
     *
     * @return array
     */
    public function validateProductWithCorrectAmountsDataProvider(): array
    {
        return [
            'missing_max_amount' => ['20', ''],
            'missing_min_amount' => ['', '20'],
            'equal_amounts' => ['20', '20'],
            'max_amount_greater_than_min' => ['10', '20'],
            'amounts_with_commas' => ['10,25', '10,30'],
        ];
    }

    /**
     * Check exception is thrown if minimum Amount is greater than maximum one.
     *
     * @return void
     */
    public function testValidateProductWithIncorrectAmounts(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string) __('Please enter a valid price range.'));

        $this->testValidateProductWithCorrectAmounts('10,30', '10,25');
    }
}
