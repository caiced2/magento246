<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Grouped;

use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Catalog\Test\Fixture\Virtual as VirtualProductFixture;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test searching products when configuration disallows it
 */
class DisallowedSearchTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a logged in customer searches using a term shared by all products
     * Then only products in Allowed Category are shown
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Default Category',
                'sku' => 'simple',
                'category_ids' => [2], // Default Category
            ],
            'simple_product_in_default_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Allowed Category',
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                'price' => 10,
            ],
            'simple_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Denied Category',
                'sku' => 'simple-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                'price' => 10,
            ],
            'simple_product_in_denied_category'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product in Allowed Category',
                'sku' => 'virtual-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                'price' => 10,
            ],
            'virtual_product_in_allowed_category'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product in Denied Category',
                'sku' => 'virtual-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                'price' => 10,
            ],
            'virtual_product_in_denied_category'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-allowed',
                'category_ids' => ['$allowed_category.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 2],
                    ['sku' => '$virtual_product_in_allowed_category.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped-product-allowed'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-denied',
                'category_ids' => ['$denied_category.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_denied_category.sku$', 'qty' => 2],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped-product-denied'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_DENY,
                'grant_catalog_product_price' => Permission::PERMISSION_DENY,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
    ]
    public function testProductsReturnedWhenPermissionsAppliedForDefaultCustomerGroup()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "Product") {
    items {
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
      ... on GroupedProduct {
        items {
          position
          product {
            sku
            price_range {
              maximum_price {
                regular_price {
                  value
                }
              }
            }
          }
          qty
        }
      }
    }
    total_count
  }
}
QUERY;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );

        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'simple-product-in-allowed-category',
                'virtual-product-in-allowed-category'
            ],
            array_column($response['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And "Disallow Catalog Search By" is set to "General" (i.e. a logged in customer) and "Wholesale"
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a logged in customer searches for a term shared by all products
     * Then an exception is raised with message stating "Product search has been disabled."
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/deny_catalog_search 1,2
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Default Category',
                'sku' => 'simple',
                'category_ids' => [2], // Default Category
            ],
            'simple_product_in_default_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Allowed Category',
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                'price' => 10,
            ],
            'simple_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Denied Category',
                'sku' => 'simple-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                'price' => 10,
            ],
            'simple_product_in_denied_category'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product in Allowed Category',
                'sku' => 'virtual-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                'price' => 10,
            ],
            'virtual_product_in_allowed_category'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product in Denied Category',
                'sku' => 'virtual-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                'price' => 10,
            ],
            'virtual_product_in_denied_category'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-allowed',
                'category_ids' => ['$allowed_category.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 2],
                    ['sku' => '$virtual_product_in_allowed_category.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped-product-allowed'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-denied',
                'category_ids' => ['$denied_category.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_denied_category.sku$', 'qty' => 2],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped-product-denied'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_DENY,
                'grant_catalog_product_price' => Permission::PERMISSION_DENY,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
    ]
    public function testExceptionReceivedWhenSearchDisallowed()
    {
        $this->reindexCatalogPermissions();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product search has been disabled.');

        $query = <<<QUERY
{
  products(search: "Product") {
    items {
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
      ... on GroupedProduct {
        items {
          position
          product {
            sku
            price_range {
              maximum_price {
                regular_price {
                  value
                }
              }
            }
          }
          qty
        }
      }
    }
    total_count
  }
}
QUERY;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());

        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category");
    }
}
