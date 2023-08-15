<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin\Quote;

use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data as DataHelper;
use Magento\GoogleTagManager\Model\Plugin\Quote\SetGoogleAnalyticsOnCartRemove;
use Magento\Quote\Model\Quote as Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetGoogleAnalyticsOnCartRemoveTest extends TestCase
{
    /** @var SetGoogleAnalyticsOnCartRemove */
    private $model;

    /** @var QuoteItem|MockObject */
    private $quoteItem;

    /** @var Quote|MockObject */
    private $quote;

    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    /**
     * @var DataHelper|MockObject
     */
    private $helper;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQty', 'getItemId'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemById'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['register', 'unregister'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            SetGoogleAnalyticsOnCartRemove::class,
            [
                'helper' => $this->helper,
                'registry' => $this->registry
            ]
        );
    }

    /**
     * @param int $callCount
     * @param int $getQtyCallCount
     * @param array $quoteItemQtys
     * @param $itemId
     * @param $quoteItemId
     * @param bool $quoteHasItem
     *
     * @dataProvider updateItemDataProvider
     */
    public function testAroundUpdateItem(
        $callCount,
        $getQtyCallCount,
        $quoteItemQtys,
        $itemId,
        $quoteItemId,
        $quoteHasItem = true
    ) {
        $params = null;
        $buyRequest = false;

        $proceed = function () {
            return $this->quoteItem;
        };

        $this->quoteItem->method('getItemId')
            ->willReturn($quoteItemId);

        $this->quoteItem->expects($this->exactly($getQtyCallCount))
            ->method('getQty')
            ->will($this->onConsecutiveCalls($quoteItemQtys[0], $quoteItemQtys[1]));

        $this->quote->expects($this->once())
            ->method('getItemById')
            ->willReturn($quoteHasItem ? $this->quoteItem : false);

        $this->helper->expects($this->exactly($callCount))
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->registry->expects($this->exactly($callCount))
            ->method('unregister')
            ->with('GoogleTagManager_products_to_remove');

        $this->registry->expects($this->exactly($callCount))
            ->method('register')
            ->with('GoogleTagManager_products_to_remove');

        $this->model->aroundUpdateItem($this->quote, $proceed, $itemId, $buyRequest, $params);
    }

    /**
     * @return array
     */
    public function updateItemDataProvider()
    {
        return [
            'ItemWithQuoteItemQtyMoreThanQuoteQty' => [0, 2, ['2', '3'], 1, 1],
            'ItemWithQuoteItemQtyLessThanQuoteQty' => [1, 3, ['2', '1'], 1, 1],
            'ChangeItemOption' => [0, 2, ['1', '1'], 1, 2],
            'ChangeQtyAndItemOption' => [0, 2, ['2', '1'], 1, 2],
            'QuoteHasNoItem' => [0, 1, ['2', '2'], null, null, false],
        ];
    }

    /**
     * @param $callCount
     * @param bool $quoteHasItem
     *
     * @dataProvider removeItemDataProvider
     */
    public function testAfterRemoveItemWithQuoteItemQtyMoreThanQuoteQty($callCount, $quoteHasItem = true)
    {
        $this->quoteItem->expects($this->exactly($callCount))
            ->method('getQty')
            ->willReturn(1);

        $this->quote->expects($this->once())
            ->method('getItemById')
            ->willReturn($quoteHasItem ? $this->quoteItem : false);

        $this->helper->expects($this->exactly($callCount))
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->registry->expects($this->exactly($callCount))
            ->method('unregister')
            ->with('GoogleTagManager_products_to_remove');

        $this->registry->expects($this->exactly($callCount))
            ->method('register')
            ->with('GoogleTagManager_products_to_remove');

        $this->model->afterRemoveItem($this->quote, $this->quoteItem, 1);
    }

    /**
     * @return array
     */
    public function removeItemDataProvider()
    {
        return [
            'QuoteHasItem' => [1],
            'QuoteHasNoItem' => [0, false]
        ];
    }
}
