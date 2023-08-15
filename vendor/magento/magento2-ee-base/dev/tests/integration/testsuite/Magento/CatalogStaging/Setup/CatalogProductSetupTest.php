<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogStaging\Setup;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\TestCase;

class CatalogProductSetupTest extends TestCase
{
    /**
     * @var CatalogProductSetup
     */
    private $catalogProductSetup;

    /**
     * @var Collection
     */
    private $productCollection;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->catalogProductSetup = $objectManager->get(CatalogProductSetup::class);
        $this->productCollection = $objectManager->get(Collection::class);
        /** @var ModuleDataSetupInterface $setup */
        $this->setup = $objectManager->get(ModuleDataSetupInterface::class);
    }

    /**
     * Test that the original data of a particular product stays unchanged
     * after it was cloned and that cloned version was updated
     * @magentoDataFixture Magento/CatalogStaging/_files/simple_product_with_all_fields_and_custom_options.php
     */
    public function testProcessProductWithCustomOptions()
    {
        $newProductRowId = 2;
        $optionIndx = 0;

        $refClass = new \ReflectionClass($this->catalogProductSetup);
        $this->catalogProductSetup->execute($this->setup);

        $property = $refClass->getProperty('productEntity');
        $property->setAccessible(true);
        /** @var Magento\Catalog\Model\Product $productEntity */
        $productEntity = $property->getValue($this->catalogProductSetup);

        $this->assertNotEmpty($productEntity->getOptions());
        $productOption = $productEntity->getOptions()[$optionIndx];
        $productOption->setProductId($newProductRowId);

        $originProperty = $refClass->getProperty('originProductEntities');
        $originProperty->setAccessible(true);
        $originalProductEntity = $originProperty->getValue($this->catalogProductSetup);
        $originProductOption = $originalProductEntity[$productEntity->getStoreId()]->getOptions()[$optionIndx];

        $this->assertNotEquals($originProductOption->getProductId(), $productOption->getProductId());
    }
}
