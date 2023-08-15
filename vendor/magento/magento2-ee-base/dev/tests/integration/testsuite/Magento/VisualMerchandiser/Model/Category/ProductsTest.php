<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Category;

use Magento\TestFramework\Fixture\DataFixture;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class ProductsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var Products
     */
    private $productsModel;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->productsModel = $this->objectManager->create(Products::class);
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture Magento/VisualMerchandiser/Block/Adminhtml/Category/Merchandiser/_files/products_with_websites_and_stores.php
     */
    public function testSavePositions()
    {
        $categoryId = 333;
        $positionCacheKey = 'position-cache-key';

        $this->productsModel->setCacheKey($positionCacheKey);
        $collection = $this->productsModel->getCollectionForGrid($categoryId);
        /** @var \Magento\VisualMerchandiser\Model\Position\Cache $positionCache */
        $positionCache = $this->objectManager->get(\Magento\VisualMerchandiser\Model\Position\Cache::class);

        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getId();
        }
        $this->productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($positionCacheKey);
        $this->assertEquals($productIds, array_keys($cachedPositions), 'Positions are incorrect.');

        shuffle($productIds);
        $positionCache->saveData($positionCacheKey, array_flip($productIds));
        $collection = $this->productsModel->getCollectionForGrid($categoryId);
        $this->productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($positionCacheKey);
        $this->assertEquals($productIds, array_keys($cachedPositions), 'Positions are not saved.');

        /** @var \Magento\VisualMerchandiser\Model\Sorting $sorting */
        $sorting = $this->objectManager->create(\Magento\VisualMerchandiser\Model\Sorting::class);
        $sortOption = 8; //Name\Descending
        $sortInstance = $sorting->getSortingInstance($sortOption);
        $sortedCollection = $sortInstance->sort($collection);
        $sortedCollection->clear();
        $sortedProductIds = [];
        foreach ($sortedCollection as $item) {
            $sortedProductIds[] = $item->getId();
        }
        $positionCache->saveData($positionCacheKey, array_flip($productIds), $sortOption);
        $collection = $this->productsModel->getCollectionForGrid($categoryId);
        $this->productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($positionCacheKey);
        $this->assertEquals($sortedProductIds, array_keys($cachedPositions), 'Products are not sorted.');
    }

    #[
        DataFixture('Magento/CatalogStaging/_files/simple_product_staged_changes.php'),
        DataFixture('Magento/ConfigurableProduct/_files/product_configurable.php'),
    ]
    public function testGetConfigurableProductQty(): void
    {
        $categoryId = 2;

        $collection = $this->productsModel->getCollectionForGrid($categoryId);
        $productsData = [];
        foreach ($collection->getItems() as $product) {
            $productsData[$product->getSku()] = $product->getData('stock');
        }
        self::assertEquals(['configurable' => 2000], $productsData);
    }
}
