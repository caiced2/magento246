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
 * Test searching products
 */
class ProductsSearchTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Given Catalog Permissions are enabled
     * And category "Denied category for guests" is created
     * And a permission is applied to "Denied category for guests" revoking all permissions to guests
     * And a permission is applied to "Denied category for guests" allowing all permissions to logged in customer group
     * And a grouped product is created in "Denied category for guests"
     * When a guest searches for products within the category
     * Then no products are returned in the response
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
        DataFixture(CategoryFixture::class, ['name' => 'Denied category for guests'], 'denied_category_for_guests'),
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Denied Category For Guests',
                'sku' => 'simple-product-in-denied-category-for-guests',
                'category_ids' => ['$denied_category_for_guests.id$'],
            ],
            'simple_product_in_denied_category_for_guests'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-denied-for-guests',
                'category_ids' => ['denied_category_for_guests.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_denied_category_for_guests.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped_product_denied_for_guests'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category_for_guests.id$',
                'customer_group_id' => 0, // Guest
                'grant_catalog_category_view' => Permission::PERMISSION_DENY,
                'grant_catalog_product_price' => Permission::PERMISSION_DENY,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category_for_guests.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_PARENT,
                'grant_checkout_items' => Permission::PERMISSION_PARENT,
            ]
        ),
    ]
    public function testProductsReturnedWhenPermissionsAppliedForGuests()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "Denied category product for guests") {
    items {
      name
      sku
    }
    total_count
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertEquals(0, $response['products']['total_count']);
        $this->assertEmpty($response['products']['items']);
    }

    /**
     * Given Catalog Permissions are enabled
     * And category "Denied category for guests" is created
     * And a permission is applied to "Denied category for guests" revoking all permissions to guests
     * And a permission is applied to "Denied category for guests" allowing all permissions to logged in customer group
     * And a grouped product is created in "Denied category for guests"
     * When a logged in customer searches for products within the category
     * Then the grouped product is returned in the response
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
        DataFixture(CategoryFixture::class, ['name' => 'Denied category for guests'], 'denied_category_for_guests'),
        DataFixture(
            ProductFixture::class,
            [
                'name' => 'Simple Product in Denied Category For Guests',
                'sku' => 'simple-product-in-denied-category-for-guests',
                'category_ids' => ['$denied_category_for_guests.id$'],
            ],
            'simple_product_in_denied_category_for_guests'
        ),
        DataFixture(
            GroupedProductFixture::class,
            [
                'sku' => 'grouped-product-denied-for-guests',
                'category_ids' => ['denied_category_for_guests.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_denied_category_for_guests.sku$', 'qty' => 2],
                    ['sku' => '$simple_product_in_default_category.sku$', 'qty' => 2]
                ]
            ],
            'grouped_product_denied_for_guests'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category_for_guests.id$',
                'customer_group_id' => 0, // Guest
                'grant_catalog_category_view' => Permission::PERMISSION_DENY,
                'grant_catalog_product_price' => Permission::PERMISSION_DENY,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category_for_guests.id$',
                'customer_group_id' => 1, // General (i.e. logged in customer)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_PARENT,
                'grant_checkout_items' => Permission::PERMISSION_PARENT,
            ]
        ),
    ]
    public function testProductsReturnedWhenPermissionsAppliedForDefaultCustomerGroup()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "Denied category product for guests") {
    items {
      name
      sku
    }
    total_count
  }
}
QUERY;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $header = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $header
        );

        $this->assertEquals(1, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'simple-product-in-denied-category-for-guests'
            ],
            array_column($response['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are disabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a guest searches using a term shared by all products
     * Then all products are returned in the response
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 0
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
                'price' => 100,
            ],
            'simple_product_in_denied_category'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product in Allowed Category',
                'sku' => 'virtual-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                'price' => 105,
            ],
            'virtual_product_in_allowed_category'
        ),
        DataFixture(
            VirtualProductFixture::class,
            [
                'name' => 'Virtual Product in Denied Category',
                'sku' => 'virtual-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                'price' => 103,
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
    public function testProductsReturnedWhenPermissionsConfigurationDisabled()
    {
        $query = <<<QUERY
{
  products(search: "category product") {
    total_count
    items {
      uid
      name
      sku
      ... on GroupedProduct {
        items {
          qty
          position
          product {
            sku
            name
            url_key
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
        product_links {
          position
          sku
          link_type
          linked_product_sku
          linked_product_type
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEquals(7, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'simple-product-in-allowed-category',
                'virtual-product-in-allowed-category',
                'grouped-product-denied',
                'simple-product-in-denied-category',
                'virtual-product-in-denied-category',
                'simple',
            ],
            array_column($response['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And "Allow Browsing Category" is set to "No, Redirect to Landing Page"
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a guest searches using a term shared by all products
     * Then no products are returned in the response
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/grant_catalog_category_view 0
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
    public function testWhenPermissionsConfigurationEnabledWithoutGrantCatalogCategoryViewInConfiguration()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "category product") {
    total_count
    items {
      uid
      name
      sku
      ... on GroupedProduct {
        items {
          qty
          position
          product {
            sku
            name
            url_key
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
        product_links {
          position
          sku
          link_type
          linked_product_sku
          linked_product_type
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEmpty($response['products']['items']);
    }

    /**
     * Given Catalog Permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category"
     * And a grouped product is created in "Denied Category"
     * When a guest searches using a term shared by all products
     * Then all products are returned in the response
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
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
    public function testWhenPermissionsConfigurationEnabledWithGrantCatalogCategoryViewInConfigurationGuest()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "category product") {
    total_count
    items {
      uid
      name
      sku
      ... on GroupedProduct {
        items {
          qty
          position
          product {
            sku
            name
            url_key
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
        product_links {
          position
          sku
          link_type
          linked_product_sku
          linked_product_type
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEquals(7, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'virtual-product-in-allowed-category',
                'simple-product-in-allowed-category',
                'grouped-product-denied',
                'simple-product-in-denied-category',
                'virtual-product-in-denied-category',
                'simple',
            ],
            array_column($response['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a grouped product is created in "Allowed Category" containing products in default
     * and allowed categories
     * And a grouped product is created in "Denied Category" containing the products in default and
     * denied categories
     * When a customer searches using a term shared by all products
     * Then products and options in allowed and default category are returned in the response
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
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
    public function testWhenPermissionsConfigurationEnabledWithGrantCatalogCategoryViewInConfigurationCustomer()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "category product") {
    total_count
    items {
      uid
      name
      sku
      ... on GroupedProduct {
        items {
          qty
          position
          product {
            sku
            name
            url_key
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
        product_links {
          position
          sku
          link_type
          linked_product_sku
          linked_product_type
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

        $this->assertEquals(4, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'grouped-product-allowed',
                'virtual-product-in-allowed-category',
                'simple-product-in-allowed-category',
                'simple'
            ],
            array_column($response['products']['items'], 'sku')
        );
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());

        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_product");
    }
}
