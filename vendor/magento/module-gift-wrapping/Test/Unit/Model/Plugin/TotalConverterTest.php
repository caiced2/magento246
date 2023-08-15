<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftWrapping\Model\Plugin\TotalsConverter as TotalsConverterPlugin;
use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;
use Magento\Quote\Api\Data\TotalSegmentExtensionInterface;
use Magento\Quote\Api\Data\TotalSegmentInterface;
use Magento\Quote\Model\Cart\TotalsConverter as QuoteTotalsConverter;
use Magento\Quote\Model\Quote\Address\Total as QuoteAddressTotal;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TotalConverterTest extends TestCase
{
    /**
     * @var TotalsConverterPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var TotalSegmentExtensionFactory|MockObject
     */
    private $totalSegmentExtensionFactoryMock;

    /**
     * @var QuoteTotalsConverter|MockObject
     */
    private $subjectMock;

    /**
     * @var TotalSegmentInterface|MockObject
     */
    private $totalSegmentMock;

    /**
     * @var QuoteAddressTotal|MockObject
     */
    private $addressTotalMock;

    /**
     * @var TotalSegmentExtensionInterface|MockObject
     */
    private $totalSegmentExtensionMock;

    protected function setUp(): void
    {
        $this->totalSegmentExtensionFactoryMock = $this->getMockBuilder(TotalSegmentExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(QuoteTotalsConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->totalSegmentMock = $this->getMockBuilder(TotalSegmentInterface::class)
            ->getMockForAbstractClass();
        $this->addressTotalMock = $this->getMockBuilder(QuoteAddressTotal::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getGwItemIds', 'getGwId', 'getGwPrice', 'getgwBasePrice', 'getGwItemsPrice',
                    'getGwItemsBasePrice', 'getGwAllowGiftReceipt', 'getGwAddCard', 'getGwCardPrice',
                    'getGwCardBasePrice', 'getGwTaxAmount', 'getGwBaseTaxAmount', 'getGwItemsTaxAmount',
                    'getGwItemsBaseTaxAmount', 'getGwCardTaxAmount', 'getGwCardBaseTaxAmount', 'getGwPriceInclTax',
                    'getGwBasePriceInclTax', 'getGwCardPriceInclTax', 'getGwCardBasePriceInclTax',
                    'getGwItemsPriceInclTax', 'getGwItemsBasePriceInclTax'
                ]
            )
            ->getMock();
        $this->totalSegmentExtensionMock = $this->getMockBuilder(TotalSegmentExtensionInterface::class)
            ->setMethods(
                [
                    'setGwItemIds', 'setGwOrderId', 'setGwPrice', 'setGwBasePrice', 'setGwItemsPrice',
                    'setGwAllowGiftReceipt', 'setGwAddCard', 'setGwCardPrice', 'setGwCardBasePrice',
                    'setGwTaxAmount', 'setGwBaseTaxAmount', 'setGwItemsTaxAmount', 'setGwCardTaxAmount',
                    'setGwItemsBaseTaxAmount', 'setGwItemsBasePrice', 'setGwCardBaseTaxAmount', 'setGwPriceInclTax',
                    'setGwBasePriceInclTax', 'setGwCardPriceInclTax', 'setGwCardBasePriceInclTax',
                    'setGwItemsPriceInclTax', 'setGwItemsBasePriceInclTax'
                ]
            )
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            TotalsConverterPlugin::class,
            ['totalSegmentExtensionFactory' => $this->totalSegmentExtensionFactoryMock]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterProcess()
    {
        $gwItemIds = [1, 2, 3];
        $gwId = 100;
        $gwPrice = 500;
        $gwBasePrice = 600;
        $gwItemsPrice = 300;
        $gwItemsBasePrice = 700;
        $gwAllowGiftReceipt = true;
        $gwAddCard = false;
        $gwCardPrice = 90;
        $gwCardBasePrice = 80;
        $gwTaxAmount = 800;
        $gwBaseTaxAmount = 1000;
        $gwItemsTaxAmount = 98;
        $gwItemsBaseTaxAmount = 77;
        $gwCardTaxAmount = 67;
        $gwCardBaseTaxAmount = 932;
        $gwPriceInclTax = $gwPrice + $gwTaxAmount;
        $gwBasePriceInclTax = $gwBasePrice + $gwBaseTaxAmount;
        $gwCardPriceInclTax = $gwCardPrice + $gwCardTaxAmount;
        $gwCardBasePriceInclTax = $gwCardBasePrice + $gwCardBaseTaxAmount;
        $gwItemsPriceInclTax = $gwItemsPrice + $gwItemsTaxAmount;
        $gwItemsBasePriceInclTax = $gwItemsBasePrice + $gwItemsBaseTaxAmount;
        $result = ['giftwrapping' => $this->totalSegmentMock];

        $this->totalSegmentExtensionFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->willReturn($this->totalSegmentExtensionMock);

        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemIds')
            ->willReturn($gwItemIds);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwId')
            ->willReturn($gwId);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwPrice')
            ->willReturn($gwPrice);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getgwBasePrice')
            ->willReturn($gwBasePrice);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemsPrice')
            ->willReturn($gwItemsPrice);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemsBasePrice')
            ->willReturn($gwItemsBasePrice);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwAllowGiftReceipt')
            ->willReturn($gwAllowGiftReceipt);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwAddCard')
            ->willReturn($gwAddCard);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwCardPrice')
            ->willReturn($gwCardPrice);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwCardBasePrice')
            ->willReturn($gwCardBasePrice);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwTaxAmount')
            ->willReturn($gwTaxAmount);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwBaseTaxAmount')
            ->willReturn($gwBaseTaxAmount);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemsTaxAmount')
            ->willReturn($gwItemsTaxAmount);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemsBaseTaxAmount')
            ->willReturn($gwItemsBaseTaxAmount);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwCardTaxAmount')
            ->willReturn($gwCardTaxAmount);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwCardBaseTaxAmount')
            ->willReturn($gwCardBaseTaxAmount);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwPriceInclTax')
            ->willReturn($gwPriceInclTax);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwBasePriceInclTax')
            ->willReturn($gwBasePriceInclTax);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwCardPriceInclTax')
            ->willReturn($gwCardPriceInclTax);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwCardBasePriceInclTax')
            ->willReturn($gwCardBasePriceInclTax);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemsPriceInclTax')
            ->willReturn($gwItemsPriceInclTax);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGwItemsBasePriceInclTax')
            ->willReturn($gwItemsBasePriceInclTax);

        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemIds')
            ->with($gwItemIds)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwOrderId')
            ->with($gwId)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwPrice')
            ->with($gwPrice)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwBasePrice')
            ->with($gwBasePrice)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemsPrice')
            ->with($gwItemsPrice)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwAllowGiftReceipt')
            ->with($gwAllowGiftReceipt)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwAddCard')
            ->with($gwAddCard)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwCardPrice')
            ->with($gwCardPrice)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwCardBasePrice')
            ->with($gwCardBasePrice)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwTaxAmount')
            ->with($gwTaxAmount)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwBaseTaxAmount')
            ->with($gwBaseTaxAmount)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemsTaxAmount')
            ->with($gwItemsTaxAmount)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwCardTaxAmount')
            ->with($gwCardTaxAmount)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemsBaseTaxAmount')
            ->with($gwItemsBaseTaxAmount)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemsBasePrice')
            ->with($gwItemsBasePrice)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwCardBaseTaxAmount')
            ->with($gwCardBaseTaxAmount)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwPriceInclTax')
            ->with($gwPriceInclTax)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwBasePriceInclTax')
            ->with($gwBasePriceInclTax)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwCardPriceInclTax')
            ->with($gwCardPriceInclTax)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwCardBasePriceInclTax')
            ->with($gwCardBasePriceInclTax)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemsPriceInclTax')
            ->with($gwItemsPriceInclTax)
            ->willReturnSelf();
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGwItemsBasePriceInclTax')
            ->with($gwItemsBasePriceInclTax)
            ->willReturnSelf();

        $this->totalSegmentMock->expects(static::atLeastOnce())
            ->method('setExtensionAttributes')
            ->with($this->totalSegmentExtensionMock)
            ->willReturnSelf();

        $this->assertEquals(
            $result,
            $this->plugin->afterProcess($this->subjectMock, $result, ['giftwrapping' => $this->addressTotalMock])
        );
    }

    public function testAfterProcessNoAddressTotal()
    {
        $result = [];

        $this->totalSegmentMock->expects(static::never())
            ->method('setExtensionAttributes');

        $this->assertEquals($result, $this->plugin->afterProcess($this->subjectMock, $result, []));
    }
}
