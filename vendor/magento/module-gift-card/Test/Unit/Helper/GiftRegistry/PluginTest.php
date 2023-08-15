<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Helper\GiftRegistry;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCard\Helper\GiftRegistry\Plugin as GiftRegistryHelperPlugin;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;
use Magento\GiftRegistry\Helper\Data as DataHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * @var GiftRegistryHelperPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var DataHelper|MockObject
     */
    private $subjectMock;

    /**
     * @var QuoteItem|MockObject
     */
    private $quoteItemMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var ProductType|MockObject
     */
    private $productTypeMock;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->subjectMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductType', 'getProductId', 'getTypeId'])
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTypeMock = $this->getMockBuilder(ProductType::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            GiftRegistryHelperPlugin::class,
            ['productRepository' => $this->productRepositoryMock]
        );
    }

    public function testAfterCanAddToGiftRegistryPhysicalCard()
    {
        $productId = 333222555;

        $this->quoteItemMock->expects(static::once())
            ->method('getProductType')
            ->willReturn(ProductType::TYPE_GIFTCARD);
        $this->quoteItemMock->expects(static::once())
            ->method('getProductId')
            ->willReturn($productId);
        $this->productRepositoryMock->expects(static::once())
            ->method('getById')
            ->with($productId)
            ->willReturn($this->productMock);
        $this->productMock->expects(static::once())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeMock);
        $this->productTypeMock->expects(static::once())
            ->method('isTypePhysical')
            ->willReturn(true);

        $this->assertTrue(
            $this->plugin->afterCanAddToGiftRegistry($this->subjectMock, true, $this->quoteItemMock)
        );
    }

    public function testAfterCanAddToGiftRegistryVirtualCard()
    {
        $this->quoteItemMock->expects(static::never())
            ->method('getProductType');
        $this->quoteItemMock->expects(static::never())
            ->method('getTypeId');

        $this->assertFalse(
            $this->plugin->afterCanAddToGiftRegistry($this->subjectMock, false, $this->quoteItemMock)
        );
    }
}
