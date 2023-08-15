<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Block\Catalog\Product\ProductList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class RelatedTest extends TestCase
{
    /**
     * @var Related
     */
    private $model;

    /**
     * @var Registry
     */
    private $registry;

    protected function setUp(): void
    {
        $this->model = Bootstrap::getObjectManager()->create(Related::class);
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
    }

    protected function tearDown(): void
    {
        $this->registry->unregister('product');
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Catalog/_files/products_related_multiple.php
     */
    public function testGetItemCollection(): void
    {
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple_with_cross');
        $this->registry->register('product', $product);

        $items = $this->model->getItemCollection();
        $this->assertCount(2, $items);
        $this->assertArrayHasKey(1, $items);
        $this->assertEquals(1, $items[1]->getId());
        $this->assertArrayHasKey(3, $items);
        $this->assertEquals(3, $items[3]->getId());
    }
}
