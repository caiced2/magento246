<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Configurable;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;

/**
 * Test searching configurable products
 */
class ProductsSearchTest extends GraphQlAbstract
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_configurable_products_deny_variant_for_guests.php
     */
    public function testPermissionsDisabledInConfiguration()
    {
        $response = $this->graphQlQuery($this->getQuery());

        $this->assertEquals(3, $response['products']['total_count']);
        $this->assertEquals(3, sizeof($response['products']['items']));
        $this->assertEquals('configurable', $response['products']['items'][0]['sku']);
        $this->assertEquals(2, sizeof($response['products']['items'][0]['variants']));
        $this->assertEquals('simple_10', $response['products']['items'][0]['variants'][0]['product']['sku']);
        $this->assertEquals('simple_20', $response['products']['items'][0]['variants'][1]['product']['sku']);
        $this->assertEquals('simple_10', $response['products']['items'][1]['sku']);
        $this->assertEquals('simple_20', $response['products']['items'][2]['sku']);
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/grant_catalog_category_view 0
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_configurable_products_deny_variant_for_guests.php
     */
    public function testPermissionsEnabledWithoutGrantCatalogCategoryViewInConfiguration()
    {
        $this->reindexCatalogPermissions();

        $response = $this->graphQlQuery($this->getQuery());

        $this->assertEquals(0, $response['products']['total_count']);
        $this->assertEquals(0, sizeof($response['products']['items']));
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_configurable_products_deny_variant_for_guests.php
     */
    public function testPermissionsEnabledWithGrantCatalogCategoryViewInConfigurationForGuest()
    {
        $this->reindexCatalogPermissions();

        $response = $this->graphQlQuery($this->getQuery());
        $this->assertEquals(2, sizeof($response['products']['items']));
        $this->assertEquals('configurable', $response['products']['items'][0]['sku']);
        $this->assertEquals(1, sizeof($response['products']['items'][0]['variants']));
        $this->assertEquals('simple_10', $response['products']['items'][0]['variants'][0]['product']['sku']);
        $this->assertEquals('simple_10', $response['products']['items'][1]['sku']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_configurable_products_deny_variant_for_guests.php
     */
    public function testPermissionsEnabledWithGrantCatalogCategoryViewInConfigurationForDefaultCustomerGroup()
    {
        $this->reindexCatalogPermissions();

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );
        $productsInfo = array_column($response['products']['items'], 'sku');
        $this->assertEquals(3, $response['products']['total_count']);
        $this->assertEquals(3, sizeof($response['products']['items']));
        $this->assertEquals('configurable', $response['products']['items'][0]['sku']);
        $this->assertEquals(2, sizeof($response['products']['items'][0]['variants']));
        $this->assertEquals('simple_10', $response['products']['items'][0]['variants'][0]['product']['sku']);
        $this->assertEquals('simple_20', $response['products']['items'][0]['variants'][1]['product']['sku']);
        $this->assertContains('simple_10', $productsInfo);
        $this->assertContains('simple_20', $productsInfo);
    }

    /**
     * @return string
     */
    private function getQuery(): string
    {
        $query = <<<QUERY
{
  products(search:"configurable") {
    items {
      sku
      ... on ConfigurableProduct {
        variants {
          product {
            sku
            price_range {
              maximum_price {
                regular_price {
                  value
                }
              }
              minimum_price {
                regular_price {
                  value
                }
              }
            }
          }
        }
      }
    }
    total_count
  }
}
QUERY;
        return $query;
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_product", $out);
    }
}
