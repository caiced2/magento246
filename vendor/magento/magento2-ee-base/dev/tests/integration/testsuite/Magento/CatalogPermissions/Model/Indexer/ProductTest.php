<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogPermissions\Model\Indexer\Product as IndexerProduct;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Index;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for product permissions reindex process
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 *
 * Tests how category permission affects indexation mechanism
 */
class ProductTest extends TestCase
{
    /**
     * @var Index
     */
    protected $indexTable;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->indexTable = $this->objectManager->create(Index::class);
        $this->product = $this->objectManager->create(Product::class);
    }

    /**
     * Reindex all test
     *
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/permission.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/product.php
     */
    public function testReindexAll()
    {
        /** @var  $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->objectManager->create(Indexer::class);
        $indexer->load(IndexerProduct::INDEXER_ID);
        $indexer->reindexAll();
        $product = $this->getProduct();

        $productData = array_merge(
            ['product_id' => $product->getId(), 'store_id' => $product->getStoreId()],
            $this->getProductData()
        );

        $reindexProductData = $this->indexTable->getIndexForProduct($product->getId(), 1, 1);
        $this->assertEquals($productData['product_id'], $reindexProductData[$product->getId()]['product_id']);
        $this->assertEquals($productData['store_id'], $reindexProductData[$product->getId()]['store_id']);
        $this->assertEquals(
            $productData['grant_catalog_category_view'],
            $reindexProductData[$product->getId()]['grant_catalog_category_view']
        );
        $this->assertEquals(
            $productData['grant_catalog_product_price'],
            $reindexProductData[$product->getId()]['grant_catalog_product_price']
        );
        $this->assertEquals(
            $productData['grant_checkout_items'],
            $reindexProductData[$product->getId()]['grant_checkout_items']
        );

        $product->setStatus(Status::STATUS_DISABLED);
        $product->save();
        $this->assertEmpty($this->indexTable->getIndexForProduct($product->getId(), 1, 1));
    }

    /**
     * Get product data
     *
     * @return array
     */
    protected function getProductData()
    {
        return [
            'grant_catalog_category_view' => '-2',
            'grant_catalog_product_price' => '-2',
            'grant_checkout_items' => '-2',
            'customer_group_id' => 1,
            'index_id' => 2
        ];
    }

    /**
     * Get product
     *
     * @return Product
     */
    protected function getProduct()
    {
        return $this->product->load(150);
    }
}
