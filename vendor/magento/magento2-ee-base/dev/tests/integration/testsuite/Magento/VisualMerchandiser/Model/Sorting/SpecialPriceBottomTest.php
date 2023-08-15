<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Sorting;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 */
class SpecialPriceBottomTest extends TestCase
{
    /**
     * @var SpecialPriceBottom
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = Bootstrap::getObjectManager()->create(SpecialPriceBottom::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_with_price_on_second_website.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     */
    public function testSort(): void
    {
        $collection = Bootstrap::getObjectManager()->create(ProductCollection::class);
        $this->model->sort($collection);
        $collection->load();
        $this->assertEquals('simple2', $collection->getFirstItem()->getSku());
        $this->assertEquals('second-website-price-product', $collection->getLastItem()->getSku());
    }
}
