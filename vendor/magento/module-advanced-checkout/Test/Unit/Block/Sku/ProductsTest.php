<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Sku;

use Magento\AdvancedCheckout\Block\Sku\Products;
use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductsTest extends TestCase
{
    /** @var Products */
    protected $products;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Data|MockObject */
    protected $checkoutHelperMock;

    /** @var MockObject */
    protected $stockItemMock;

    /**
     * @var MockObject
     */
    protected $stockRegistry;

    protected function setUp(): void
    {
        $this->checkoutHelperMock = $this->createMock(Data::class);
        $this->checkoutHelperMock->expects($this->once())
            ->method('getFailedItems')
            ->willReturn([]);

        $this->stockRegistry = $this->getMockBuilder(StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem'])
            ->getMock();

        $this->stockItemMock = $this->createPartialMock(
            Item::class,
            ['getIsInStock']
        );

        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->willReturn($this->stockItemMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->products = $this->objectManagerHelper->getObject(
            Products::class,
            [
                'checkoutData' => $this->checkoutHelperMock,
                'stockRegistry' => $this->stockRegistry
            ]
        );
    }

    /**
     * @param array $config
     * @param bool $result
     * @dataProvider showItemLinkDataProvider
     */
    public function testShowItemLink($config, $result)
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('isComposite')
            ->willReturn($config['is_composite']);

        $quoteItem = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $quoteItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        if ($config['is_composite']) {
            $productsInGroup = [
                [$this->getChildProductMock($config['is_in_stock'])],
            ];

            $typeInstance = $this->createMock(Simple::class);
            $typeInstance->expects($this->once())
                ->method('getProductsToPurchaseByReqGroups')
                ->with($product)
                ->willReturn($productsInGroup);

            $product->expects($this->once())
                ->method('getTypeInstance')
                ->willReturn($typeInstance);

            $store = $this->createMock(Store::class);
            $quoteItem->expects($this->once())
                ->method('getStore')
                ->willReturn($store);
        }

        $this->assertSame($result, $this->products->showItemLink($quoteItem));
    }

    /**
     * @param bool $isInStock
     * @return MockObject
     */
    protected function getChildProductMock($isInStock)
    {
        $product = $this->getMockBuilder(Product::class)
            ->addMethods(['hasStockItem'])
            ->onlyMethods(['isDisabled', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('hasStockItem')
            ->willReturn(true);
        if ($isInStock) {
            $product->expects($this->once())
                ->method('isDisabled')
                ->willReturn(false);
        }
        $product->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $this->stockItemMock->expects($this->once())
            ->method('getIsInStock')
            ->willReturn($isInStock);
        return $product;
    }

    /**
     * @return array
     */
    public function showItemLinkDataProvider()
    {
        return [
            [
                ['is_composite' => false], true,
            ],
            [
                ['is_composite' => true, 'is_in_stock' => true], true
            ],
            [
                ['is_composite' => true, 'is_in_stock' => false], false
            ],
        ];
    }
}
