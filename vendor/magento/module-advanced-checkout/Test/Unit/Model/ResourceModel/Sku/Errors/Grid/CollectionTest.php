<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\ResourceModel\Sku\Errors\Grid;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\ResourceModel\Sku\Errors\Grid\Collection;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testLoadData()
    {
        $productId = '3';
        $websiteId = '1';
        $sku = 'my sku';
        $typeId = 'giftcard';

        $cart = $this->getCartMock($productId, $websiteId, $sku);
        $product = $this->getProductMock($typeId);
        $priceCurrencyMock = $this->getPriceCurrencyMock();
        $entity = $this->getEntityFactoryMock();
        $stockStatusMock = $this->getMockBuilder(StockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $registryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $registryMock->expects($this->any())
            ->method('getStockStatus')
            ->withAnyParameters()
            ->willReturn($stockStatusMock);

        $objectManager = new ObjectManager($this);
        $collection = $objectManager->getObject(
            Collection::class,
            [
                'entityFactory' => $entity,
                'cart' => $cart,
                'productModel' => $product,
                'priceCurrency' => $priceCurrencyMock,
                'stockRegistry' => $registryMock
            ]
        );
        $collection->loadData();

        foreach ($collection->getItems() as $item) {
            $product = $item->getProduct();
            if ($item->getCode() != 'failed_sku') {
                $this->assertEquals($typeId, $product->getTypeId());
                $this->assertEquals('10.00', $item->getPrice());
            }
        }
    }

    /**
     * Return cart mock instance
     *
     * @return MockObject|Cart
     */
    protected function getCartMock($productId, $storeId, $sku)
    {
        $cartMock = $this->getMockBuilder(
            Cart::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getFailedItems', 'getStore']
            )->getMock();
        $cartMock->expects(
            $this->any()
        )->method(
            'getFailedItems'
        )->willReturn(
            [
                [
                    "item" => ["id" => $productId, "is_qty_disabled" => "false", "sku" => $sku, "qty" => "1"],
                    "code" => "failed_configure",
                    "orig_qty" => "7",
                ],
                [
                    "item" => ["sku" => 'invalid', "qty" => "1"],
                    "code" => "failed_sku",
                    "orig_qty" => "1"
                ],
            ]
        );
        $storeMock = $this->getStoreMock($storeId);
        $cartMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        return $cartMock;
    }

    /**
     * Return store mock instance
     *
     * @return MockObject|Store
     */
    protected function getStoreMock($websiteId)
    {
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);

        return $storeMock;
    }

    /**
     * Return product mock instance
     *
     * @return MockObject|Product
     */
    protected function getProductMock($typeId)
    {
        $productMock = $this->createPartialMock(
            Product::class,
            ['_beforeLoad', '_afterLoad', '_getResource', 'load', 'getPriceModel', 'getPrice', 'getTypeId']
        );
        $productMock->expects($this->once())->method('getTypeId')->willReturn($typeId);
        $productMock->expects($this->once())->method('getPrice')->willReturn('10.00');

        return $productMock;
    }

    /**
     * Return PriceCurrencyInterface mock instance
     *
     * @return MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected function getPriceCurrencyMock()
    {
        $priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $priceCurrencyMock->expects($this->any())->method('format')->willReturnArgument(0);

        return $priceCurrencyMock;
    }

    /**
     * Return entityFactory mock instance
     *
     * @return MockObject|EntityFactory
     */
    protected function getEntityFactoryMock()
    {
        $entityFactoryMock = $this->createMock(EntityFactory::class);

        return $entityFactoryMock;
    }
}
