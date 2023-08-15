<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VisualMerchandiser\Model\Rules\Factory as RulesFactory;
use PHPUnit\Framework\TestCase;

class QuantityAndStockStatusTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_product_with_two_child_products.php
     */
    public function testApplyToCollection()
    {
        $objectManager = Bootstrap::getObjectManager();

        $rulesFactory = $objectManager->get(RulesFactory::class);
        $rule = [
            'attribute' => 'quantity_and_stock_status',
            'operator' => 'gteq',
            'value' => '100',
            'logic' => 'OR',
        ];
        $quantityAndStockStatusRule = $rulesFactory->create($rule);
        $collection = $objectManager->create(ProductCollection::class);
        $quantityAndStockStatusRule->applyToCollection($collection);

        $products = $collection->getItems();
        $this->assertCount(3, $products);
    }
}
