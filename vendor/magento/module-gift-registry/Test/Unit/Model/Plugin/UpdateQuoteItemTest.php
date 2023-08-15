<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\Framework\DataObject;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\Plugin\UpdateQuoteItem;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\GiftRegistry\Model\Plugin\UpdateQuoteItem class.
 */
class UpdateQuoteItemTest extends TestCase
{
    /**
     * @var UpdateQuoteItem
     */
    private $plugin;

    /**
     * @var Data|MockObject
     */
    private $giftRegistryDataMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var QuoteItem|MockObject
     */
    private $quoteItemMock;

    /**
     * @var DataObject|MockObject
     */
    private $buyRequestMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->giftRegistryDataMock = $this->getMockBuilder(Data::class)
            ->onlyMethods(['isEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->onlyMethods(['getItemById'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->addMethods(['getGiftregistryItemId', 'setGiftregistryItemId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->buyRequestMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = new UpdateQuoteItem($this->giftRegistryDataMock);
    }

    /**
     * @return void
     */
    public function testAfterUpdateItem(): void
    {
        $itemId = 10;
        $giftRegistryId = 100;

        $this->giftRegistryDataMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getItemById')
            ->with($itemId)
            ->willReturn($this->quoteItemMock);

        $this->quoteItemMock->expects($this->once())
            ->method('getGiftregistryItemId')
            ->willReturn($giftRegistryId);

        $this->quoteItemMock->expects($this->once())
            ->method('setGiftregistryItemId')
            ->with($giftRegistryId)
            ->willReturnSelf();

        $this->assertSame(
            $this->quoteItemMock,
            $this->plugin->afterUpdateItem(
                $this->quoteMock,
                $this->quoteItemMock,
                $itemId,
                $this->buyRequestMock
            )
        );
    }

    /**
     * @return void
     */
    public function testAfterUpdateItemWithDisabledGiftRegistry(): void
    {
        $itemId = 10;

        $this->giftRegistryDataMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->quoteItemMock->expects($this->never())
            ->method('setGiftregistryItemId');

        $this->assertSame(
            $this->quoteItemMock,
            $this->plugin->afterUpdateItem(
                $this->quoteMock,
                $this->quoteItemMock,
                $itemId,
                $this->buyRequestMock
            )
        );
    }
}
