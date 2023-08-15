<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftRegistry\Model\Plugin\QuoteItem as QuoteItemPlugin;
use Magento\Quote\Model\Quote\Address\Item as QuoteAddressItem;
use Magento\Quote\Model\Quote\Item\AbstractItem as AbstractQuoteItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteItemTest extends TestCase
{
    /**
     * @var QuoteItemPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var QuoteToOrderItem|MockObject
     */
    private $subjectMock;

    /**
     * @var OrderItemInterface|MockObject
     */
    private $resultMock;

    /**
     * @var AbstractQuoteItem[]|MockObject[]
     */
    private $quoteItemMocks;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(QuoteToOrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(OrderItemInterface::class)
            ->setMethods(['setGiftregistryItemId'])
            ->getMockForAbstractClass();
        $this->quoteItemMocks = [
            AbstractQuoteItem::class => $this->getMockBuilder(AbstractQuoteItem::class)
                ->disableOriginalConstructor()
                ->setMethods(['getGiftregistryItemId'])
                ->getMockForAbstractClass(),
            QuoteAddressItem::class => $this->getMockBuilder(QuoteAddressItem::class)
                ->disableOriginalConstructor()
                ->setMethods(['getQuoteItem'])
                ->getMock()
        ];

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(QuoteItemPlugin::class);
    }

    /**
     * @param string $quoteItemType
     * @param int $getQuoteItemCalls
     *
     * @dataProvider afterConvertDataProvider
     */
    public function testAfterConvert($quoteItemType, $getQuoteItemCalls)
    {
        $registryItemId = 1;

        $this->setQuoteItemExpectations($registryItemId, $getQuoteItemCalls);
        $this->resultMock->expects(static::once())
            ->method('setGiftregistryItemId')
            ->with($registryItemId)
            ->willReturnSelf();

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterConvert(
                $this->subjectMock,
                $this->resultMock,
                $this->quoteItemMocks[$quoteItemType]
            )
        );
    }

    /**
     * @param string $quoteItemType
     * @param int $getQuoteItemCalls
     *
     * @dataProvider afterConvertDataProvider
     */
    public function testAfterConvertNotGiftRegistry($quoteItemType, $getQuoteItemCalls)
    {
        $this->setQuoteItemExpectations(null, $getQuoteItemCalls);
        $this->resultMock->expects(static::never())
            ->method('setGiftregistryItemId');

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterConvert(
                $this->subjectMock,
                $this->resultMock,
                $this->quoteItemMocks[$quoteItemType]
            )
        );
    }

    /**
     * @return array
     */
    public function afterConvertDataProvider()
    {
        return [
            ['quoteItemType' => AbstractQuoteItem::class, 'getQuoteItemCalls' => 0],
            ['quoteItemType' => QuoteAddressItem::class, 'getQuoteItemCalls' => 1]
        ];
    }

    /**
     * Set quote item expectations
     *
     * @param int|null $registryItemId
     * @param int $getQuoteItemCalls
     * @return void
     */
    private function setQuoteItemExpectations($registryItemId, $getQuoteItemCalls)
    {
        $this->quoteItemMocks[QuoteAddressItem::class]->expects(static::exactly($getQuoteItemCalls))
            ->method('getQuoteItem')
            ->willReturn($this->quoteItemMocks[AbstractQuoteItem::class]);
        $this->quoteItemMocks[AbstractQuoteItem::class]->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn($registryItemId);
    }
}
