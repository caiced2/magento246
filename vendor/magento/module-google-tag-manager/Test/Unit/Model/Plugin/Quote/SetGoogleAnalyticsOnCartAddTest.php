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
use Magento\GoogleTagManager\Model\Plugin\Quote\SetGoogleAnalyticsOnCartAdd;
use Magento\Quote\Model\Quote as Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetGoogleAnalyticsOnCartAddTest extends TestCase
{
    /** @var SetGoogleAnalyticsOnCartAdd */
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
            ->setMethods(['getQty'])
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
            SetGoogleAnalyticsOnCartAdd::class,
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
     * @param bool $quoteHasItem
     *
     * @dataProvider updateItemDataProvider
     */
    public function testAroundUpdateItem(
        $callCount,
        $getQtyCallCount,
        $quoteItemQtys,
        $quoteHasItem = true
    ) {
        $params = null;
        $buyRequest = false;
        $itemId = 1;

        $proceed = function () {
            return $this->quoteItem;
        };

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
            ->with('GoogleTagManager_products_addtocart');

        $this->registry->expects($this->exactly($callCount))
            ->method('register')
            ->with('GoogleTagManager_products_addtocart');

        $this->model->aroundUpdateItem($this->quote, $proceed, $itemId, $buyRequest, $params);
    }

    /**
     * @return array
     */
    public function updateItemDataProvider()
    {
        return [
            'ItemWithQuoteItemQtyMoreThanQuoteQty' => [1, 3, ['2', '3']],
            'ItemWithQuoteItemQtyLessThanQuoteQty' => [0, 2, ['2', '1']],
            'QuoteHasNoItem' => [1, 2, ['2', '2'], false]
        ];
    }
}
