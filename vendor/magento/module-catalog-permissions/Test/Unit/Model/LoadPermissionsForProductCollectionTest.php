<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model;

use ArrayIterator;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogPermissions\Model\LoadPermissionsForProductCollection;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for LoadPermissionsForProductCollection
 */
class LoadPermissionsForProductCollectionTest extends TestCase
{
    /**
     * @var Index
     */
    private $permissionIndex;
    /**
     * @var Data
     */
    private $catalogHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoadPermissionsForProductCollection
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionIndex = $this->createMock(Index::class);
        $this->catalogHelper = $this->createMock(Data::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->model = new LoadPermissionsForProductCollection(
            $this->permissionIndex,
            $this->catalogHelper,
            $this->storeManager
        );
    }

    /**
     * @param array $productPermissions
     * @param array $categoryPermissions
     * @param array $expectedResult
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $productPermissions, array $categoryPermissions, array $expectedResult): void
    {
        $productIds = [10, 20, 30];
        $customerGroupId = 1;
        $storeId = 1;
        $categoryId = 3;
        $websiteId = 1;
        $products = [
            10 => $this->createConfiguredMock(Product::class, ['getId' => 10, 'getCategoryIds' => [2, 3]]),
            20 => $this->createConfiguredMock(Product::class, ['getId' => 20, 'getCategoryIds' => [2, 3]]),
            30 => $this->createConfiguredMock(Product::class, ['getId' => 20, 'getCategoryIds' => [4, 5]]),
        ];
        $category = $this->createConfiguredMock(CategoryInterface::class, ['getId' => $categoryId]);
        $store = $this->createConfiguredMock(StoreInterface::class, ['getWebsiteId' => $websiteId]);
        $collection = $this->createMock(Collection::class);
        $collection->method('getItems')
            ->willReturn($products);
        $collection->method('getIterator')
            ->willReturn(new ArrayIterator($products));
        $this->permissionIndex->expects($this->once())
            ->method('getIndexForProduct')
            ->with($productIds, $customerGroupId, $storeId)
            ->willReturn($productPermissions);
        $this->catalogHelper->method('getCategory')
            ->willReturn($category);
        $this->storeManager->method('getStore')
            ->with($storeId)
            ->willReturn($store);
        $this->permissionIndex->expects($this->once())
            ->method('getIndexForCategory')
            ->with($categoryId, $customerGroupId, $storeId)
            ->willReturn($categoryPermissions);

        $this->assertEquals($expectedResult, $this->model->execute($collection, $customerGroupId, $storeId));
    }

    public function executeDataProvider(): array
    {
        return [
            [
                'productPermissions' => [
                    10 => [
                        'product_id' => 10,
                        'customer_group_id' => 1,
                        'grant_catalog_category_view' => 1,
                        'grant_catalog_product_price' => 1,
                        'grant_checkout_items' => 1,
                    ]
                ],
                'categoryPermissions' => [],
                'expectedResult' => [
                    10 => [
                        'grant_catalog_category_view' => 1,
                        'grant_catalog_product_price' => 1,
                        'grant_checkout_items' => 1,
                    ]
                ]
            ],
            [
                'productPermissions' => [
                    10 => [
                        'product_id' => 10,
                        'customer_group_id' => 1,
                        'grant_catalog_category_view' => 1,
                        'grant_catalog_product_price' => 1,
                        'grant_checkout_items' => 1,
                    ],
                    20 => [
                        'product_id' => 20,
                        'customer_group_id' => 1,
                        'grant_catalog_category_view' => 2,
                        'grant_catalog_product_price' => 2,
                        'grant_checkout_items' => 2,
                    ],
                    30 => [
                        'product_id' => 30,
                        'customer_group_id' => 1,
                        'grant_catalog_category_view' => 1,
                        'grant_catalog_product_price' => 1,
                        'grant_checkout_items' => 1,
                    ]
                ],
                'categoryPermissions' => [
                    3 => [
                        'category_id' => 3,
                        'website_id' => 1,
                        'customer_group_id' => 1,
                        'grant_catalog_category_view' => 2,
                        'grant_catalog_product_price' => 2,
                        'grant_checkout_items' => 2,
                    ]
                ],
                'expectedResult' => [
                    10 => [
                        'grant_catalog_category_view' => 2,
                        'grant_catalog_product_price' => 2,
                        'grant_checkout_items' => 2,
                    ],
                    20 => [
                        'grant_catalog_category_view' => 2,
                        'grant_catalog_product_price' => 2,
                        'grant_checkout_items' => 2,
                    ],
                    30 => [
                        'grant_catalog_category_view' => 1,
                        'grant_catalog_product_price' => 1,
                        'grant_checkout_items' => 1,
                    ]
                ]
            ]
        ];
    }
}
