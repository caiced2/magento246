<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Plugin\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    /** @var Quote */
    protected $quote;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Data|MockObject */
    protected $helper;

    /** @var Session|MockObject */
    protected $session;

    /** @var Registry|MockObject */
    protected $registry;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->session = $this->getMockBuilder(Session::class)
            ->addMethods(['hasData', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->createMock(Registry::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->quote = $this->objectManagerHelper->getObject(
            Quote::class,
            [
                'helper' => $this->helper,
                'checkoutSession' => $this->session,
                'registry' => $this->registry
            ]
        );
    }

    /**
     * @param string $type
     * @param int $qty
     * @param bool $available
     * @param mixed $setDataCall
     * @param [] $expected
     *
     * @dataProvider afterLoadDataProvider
     */
    public function testAfterLoad($type, $qty, $available, $setDataCall, $expected)
    {
        $option = $this->getMockBuilder(Option::class)
            ->addMethods(['getProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->any())->method('getProductId')->willReturn('GroupedId');

        $parentItem = $this->getMockBuilder(Item::class)
            ->addMethods(['getProductId'])
            ->onlyMethods(['getQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $parentItem->expects($this->any())->method('getQty')->willReturn(10);
        $parentItem->expects($this->any())->method('getProductId')->willReturn('ParentId');

        $item = $this->getMockBuilder(Item::class)
            ->addMethods(['getProductId'])
            ->onlyMethods(['getProductType', 'getOptionByCode', 'getId', 'getQty', 'getParentItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())->method('getProductType')->willReturn($type);
        $item->expects($this->any())->method('getOptionByCode')->with('product_type')->willReturn($option);
        $item->expects($this->any())->method('getProductId')->willReturn('ProductId');
        $item->expects($this->any())->method('getId')->willReturn('Id');
        $item->expects($this->any())->method('getQty')->willReturn($qty);
        $item->expects($this->any())->method('getParentItem')->willReturn($parentItem);

        $items = [$item];

        $subject = $this->createMock(\Magento\Quote\Model\Quote::class);
        $subject->expects($this->any())->method('getAllItems')->willReturn($items);

        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn($available);

        $this->session->expects($this->any())->method('hasData')
            ->with(Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART)
            ->willReturn(false);
        $this->session->expects($setDataCall)->method('setData')
            ->with(
                Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART,
                $expected
            );
        $this->assertSame($subject, $this->quote->afterLoad($subject, $subject));
    }

    public function afterLoadDataProvider()
    {
        return [
            ['bundle', 1, true, $this->once(), []],
            ['configurable', 2, true, $this->once(), []],
            ['grouped', 3, true, $this->once(), ['GroupedId-ProductId' => 3]],
            ['giftcard', 4, true, $this->once(), ['Id-ProductId' => 4]],
            ['simple', 5, true, $this->once(), ['Id-ParentId-ProductId' => 50]],
            ['', 0, false, $this->never(), []],
        ];
    }

    public function testAfterLoadForProductWithoutParent()
    {
        $option = $this->getMockBuilder(Option::class)
            ->addMethods(['getProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->any())->method('getProductId')->willReturn('GroupedId');

        $item = $this->getMockBuilder(Item::class)
            ->addMethods(['getProductId'])
            ->onlyMethods(['getProductType', 'getOptionByCode', 'getId', 'getQty', 'getParentItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())->method('getProductType')->willReturn('simple');
        $item->expects($this->any())->method('getProductId')->willReturn('ProductId');
        $item->expects($this->any())->method('getQty')->willReturn(17);
        $item->expects($this->any())->method('getParentItem')->willReturn(null);

        $subject = $this->createMock(\Magento\Quote\Model\Quote::class);
        $subject->expects($this->any())->method('getAllItems')->willReturn([$item]);

        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(true);

        $this->session->expects($this->any())->method('hasData')
            ->with(Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART)
            ->willReturn(false);
        $this->session->expects($this->once())->method('setData')
            ->with(
                Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART,
                ['ProductId' => 17]
            );
        $this->assertSame($subject, $this->quote->afterLoad($subject, $subject));
    }
}
