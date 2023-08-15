<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Grouped;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Catalog\Test\Fixture\Virtual as VirtualProductFixture;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test presence/absence of grouped products via categories querying with various
 * catalog permissions and customer groups
 */
class CategoriesQueryTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * Given Catalog Permissions are disabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a guest queries for categories and their products
     * Then all categories and products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 0
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
    public function testCategoriesReturnedWhenPermissionsConfigurationDisabled()
    {
        $query = <<<QUERY
{
  categories(filters: {name: {match: "Category"}}) {
    items {
      uid
      name
      product_count
      products {
        items {
          sku 
        ... on GroupedProduct {
            items {
              qty
              position
              product {
                name
                sku
                price_range {
                  minimum_price{
                    regular_price {
                      currency
                      value
                    }
                    final_price {
                      currency
                      value
                    }
                  }
                  maximum_price {
                    regular_price {
                      value
                    }
                  }
                }
              }
            }
        } 
      }
    }
  }
}
}
QUERY;
        $response = $this->graphQlQuery($query);

        list($defaultCategory, $allowedCategory, $deniedCategory) = $response['categories']['items'];

        $this->assertEquals('Default Category', $defaultCategory['name']);

        $this->assertEquals('Allowed category', $allowedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'simple-product-in-allowed-category',
                'virtual-product-in-allowed-category'
            ],
            array_column($allowedCategory['products']['items'], 'sku')
        );

        $this->assertEquals('Denied category', $deniedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-denied',
                'simple-product-in-denied-category',
                'virtual-product-in-denied-category'
            ],
            array_column($deniedCategory['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions Are Enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a guest queries for categories and their products
     * Then no categories or products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
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
    public function testCategoriesReturnedForGuest()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {name: {match: "Category"}}) {
    items {
      uid
      name
      product_count
      products {
        items {
          sku
        ... on GroupedProduct {
            items {
              qty
              position
              product {
                name
                sku
                price_range {
                  minimum_price{
                    regular_price {
                      currency
                      value
                    }
                    final_price {
                      currency
                      value
                    }

                  }
                  maximum_price {
                    regular_price {
                      value
                    }

                  }
                }
              }
            }

        }
      }
    }
  }
}
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertCount(0, $response['categories']['items']);
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to guest customer group
     * And a permission is applied to "Denied Category" revoking all permissions to guest customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a guest queries for categories and their products
     * Then only "Allowed Category" and its products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
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
                'customer_group_id' => 0, // Guest
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_ALLOW,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 0, // Guest
                'grant_catalog_category_view' => Permission::PERMISSION_DENY,
                'grant_catalog_product_price' => Permission::PERMISSION_DENY,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
    ]
    public function testAllowedCategoriesReturnedForGuest()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {name: {match: "category"}}) {
    items {
      uid
      name
      product_count
      products {
        items {
          sku
          ... on GroupedProduct {
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
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertCount(1, $response['categories']['items']);
        $allowedCategory = $response['categories']['items'][0];
        $this->assertEquals('Allowed category', $allowedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'simple-product-in-allowed-category',
                'virtual-product-in-allowed-category'
            ],
            array_column($allowedCategory['products']['items'], 'sku')
        );
        $groupedProduct = $allowedCategory['products']['items'][0];
        $this->assertEquals('grouped-product-allowed', $groupedProduct['sku']);
        $groupedProductRange = $groupedProduct["price_range"];
        $this->assertEquals(
            10,
            $groupedProductRange['maximum_price']['regular_price']['value']
        );
        $this->assertEquals(
            10,
            $groupedProductRange['minimum_price']['regular_price']['value']
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a logged in customer queries for categories and their products
     * Then only "Allowed Category" and its products are returned in the response
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
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 24],
                    ['sku' => '$virtual_product_in_allowed_category.sku$', 'qty' => 24],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 24]
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
                    ['sku' => '$simple_product_in_denied_category.sku$', 'qty' => 24],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 24],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 24]
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
    public function testCategoriesReturnedForCustomer()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {name: {match: "category"}}) {
    items {
      uid
      name
      product_count
      products {
        items {
          sku
          ... on GroupedProduct {
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
      }
    }
  }
}
QUERY;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $header =  $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $header
        );

        $this->assertCount(1, $response['categories']['items']);
        $allowedCategory = $response['categories']['items'][0];
        $this->assertEquals('Allowed category', $allowedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'simple-product-in-allowed-category',
                'virtual-product-in-allowed-category'
            ],
            array_column($allowedCategory['products']['items'], 'sku')
        );
        $groupedProduct = $allowedCategory['products']['items'][0];
        $this->assertEquals('grouped-product-allowed', $groupedProduct['sku']);
        $groupedProductRange = $groupedProduct["price_range"];
        $this->assertEquals(
            10,
            $groupedProductRange['maximum_price']['regular_price']['value']
        );
        $this->assertEquals(
            10,
            $groupedProductRange['minimum_price']['regular_price']['value']
        );
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "General" (i.e. a logged in user)
     * And a grouped product is assigned to a category that does not have any permissions applied
     * When a logged in customer queries for a specific category and its products by its id
     * Then the category and its containing product are returned
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view_groups 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple',
                'category_ids' => [2], // Default Category
            ],
            'simple_product_in_default_category'
        ),
        DataFixture(
            CategoryFixture::class,
            [
                'name' => 'Category 1',
                'parent_id' => 2,
                'is_anchor' => 1,
            ],
            'c1'
        ),
        DataFixture(
            CategoryFixture::class,
            [
                'name' => 'Category 2',
                'parent_id' => '$c1.id$',
            ],
            'c2'
        ),
        DataFixture(
            CategoryFixture::class,
            [
                'name' => 'Category 3',
                'parent_id' => '$c1.id$',
            ],
            'c3'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product Category',
                'sku' => 'virtual-product-category',
                'price' => 10,
            ],
            'virtual_product_category'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-without-permissions-applied',
                'category_ids' => ['$c2.id$'],
                'product_links' => [
                    ['sku' => '$virtual_product_category.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped-product-without-permissions-applied'
        ),
    ]
    public function testCategoriesReturnedForCustomerGroupsAllowedByConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var CategoryInterface $category1 */
        $category1 = $this->fixtures->get('c1');
        $category1Id = $category1->getId();
        $query = <<<QUERY
{
  categories(filters: {ids: {eq: "$category1Id"}}) {
    total_count
    items {
        id
        name
        children_count
        products {
            items {
                sku
            }
        }
    }
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

        $this->assertEquals(1, $response['categories']['total_count']);
        $this->assertCount(1, $response['categories']['items']);
        $this->assertEquals(2, $response['categories']['items'][0]['children_count']);
        $this->assertCount(1, $response['categories']['items'][0]['products']['items']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-without-permissions-applied',
            ],
            array_column($response['categories']['items'][0]['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "Wholesale"
     * And a grouped product is assigned to a category that does not have any permissions applied
     * When a logged in customer queries for a specific category and its products by its id
     * Then no category or products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view_groups 2
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple',
                'category_ids' => [2], // Default Category
            ],
            'simple_product_in_default_category'
        ),
        DataFixture(
            CategoryFixture::class,
            [
                'name' => 'Category 1',
                'parent_id' => 2,
                'is_anchor' => 1,
            ],
            'c1'
        ),
        DataFixture(
            CategoryFixture::class,
            [
                'name' => 'Category 2',
                'parent_id' => '$c1.id$',
            ],
            'c2'
        ),
        DataFixture(
            CategoryFixture::class,
            [
                'name' => 'Category 3',
                'parent_id' => '$c1.id$',
            ],
            'c3'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product Category',
                'sku' => 'virtual-product-category',
                'price' => 10,
            ],
            'virtual_product_category'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-without-permissions-applied',
                'category_ids' => ['$c2.id$'],
                'product_links' => [
                    ['sku' => '$virtual_product_category.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped-product-without-permissions-applied'
        ),
    ]
    public function testCategoriesReturnedForCustomerGroupsDeniedByConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var CategoryInterface $category1 */
        $category1 = $this->fixtures->get('c1');
        $category1Id = $category1->getId();

        $query = <<<QUERY
{
  categories(filters: {ids: {eq: "$category1Id"}}) {
    total_count
    items {
        children_count
        products {
            items {
                id
            }
        }
    }
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

        $this->assertEmpty($response['categories']['items']);
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
