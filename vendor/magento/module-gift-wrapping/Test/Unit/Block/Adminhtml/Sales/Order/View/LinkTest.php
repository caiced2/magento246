<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\GiftMessage\Block\Adminhtml\Sales\Order\Create\Giftoptions;
use Magento\GiftWrapping\Block\Adminhtml\Sales\Order\Create\Link;
use Magento\GiftWrapping\Helper\Data;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testCanDisplayGiftWrappingForItem()
    {
        $giftWrappingData = $this->createPartialMock(
            Data::class,
            ['isGiftWrappingAvailableForItems']
        );
        $giftWrappingData->expects($this->once())
            ->method('isGiftWrappingAvailableForItems')
            ->with(1)
            ->willReturn(true);

        $typeInstance = $this->createMock(Simple::class);

        $product = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftWrappingAvailable'])
            ->onlyMethods(['getTypeInstance'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $product->expects($this->once())->method('getGiftWrappingAvailable')->willReturn(null);

        $orderItem = $this->getMockBuilder(Item::class)
            ->addMethods(['getStoreId'])
            ->onlyMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderItem->expects($this->once())->method('getProduct')->willReturn($product);
        $orderItem->expects($this->once())->method('getStoreId')->willReturn(1);

        $block1 = $this->createPartialMock(
            Giftoptions::class,
            ['getItem']
        );
        $block1->expects($this->any())->method('getItem')->willReturn($orderItem);

        $layout = $this->createPartialMock(
            Layout::class,
            ['getParentName', 'getBlock']
        );
        $layout->expects($this->any())
            ->method('getParentName')
            ->with('nameInLayout')
            ->willReturn('parentName');
        $layout->expects($this->any())
            ->method('getBlock')
            ->with('parentName')
            ->willReturn($block1);

        $objectManager = new ObjectManager($this);
        $context = $objectManager->getObject(Context::class, ['layout' => $layout]);

        /** @var Link $websiteModel */
        $block = $objectManager->getObject(
            Link::class,
            ['context' => $context, 'giftWrappingData' => $giftWrappingData]
        );
        $block->setNameInLayout('nameInLayout');

        $this->assertTrue($block->canDisplayGiftWrappingForItem());
    }
}
