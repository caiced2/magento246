<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Bundle;

use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogPermissions\Model\Permission;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;

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
     * Given Catalog Permissions are disabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a bundle product is created in "Allowed Category"
     * And a bundle product is created in "Denied Category"
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
            BundleOptionFixture::class,
            [
                'title' => 'allowed_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_allowed_category$',
                ]
            ],
            'bundle_product_in_allowed_category_bundle_option'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'denied_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category$',
                ]
            ],
            'bundle_product_in_denied_category_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Allowed Category',
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Denied Category',
                'sku' => 'bundle-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                '_options' => ['$bundle_product_in_denied_category_bundle_option$'],
            ],
            'bundle_product_in_denied_category'
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
    items {
      name
      sku
    }
    total_count
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEquals(5, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'bundle-product-in-allowed-category',
                'simple-product-in-allowed-category',
                'bundle-product-in-denied-category',
                'simple-product-in-denied-category',
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
     * And a bundle product is created in "Allowed Category"
     * And a bundle product is created in "Denied Category"
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
            BundleOptionFixture::class,
            [
                'title' => 'allowed_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_allowed_category$',
                ]
            ],
            'bundle_product_in_allowed_category_bundle_option'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'denied_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category$',
                ]
            ],
            'bundle_product_in_denied_category_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Allowed Category',
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Denied Category',
                'sku' => 'bundle-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                '_options' => ['$bundle_product_in_denied_category_bundle_option$'],
            ],
            'bundle_product_in_denied_category'
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
    items {
      name
      sku
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
     * And a bundle product is created in "Allowed Category"
     * And a bundle product is created in "Denied Category"
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
            BundleOptionFixture::class,
            [
                'title' => 'allowed_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_allowed_category$',
                ]
            ],
            'bundle_product_in_allowed_category_bundle_option'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'denied_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category$',
                ]
            ],
            'bundle_product_in_denied_category_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Allowed Category',
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Denied Category',
                'sku' => 'bundle-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                '_options' => ['$bundle_product_in_denied_category_bundle_option$'],
            ],
            'bundle_product_in_denied_category'
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
    items {
      name
      sku
      ... on BundleProduct {
        items {
          options {
            uid
            quantity
            position
            is_default
            price
            price_type
            can_change_quantity
            label
            product {
              name
              sku
            }
          }
        }
      }
    }
    total_count
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEquals(5, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'bundle-product-in-allowed-category',
                'simple-product-in-allowed-category',
                'bundle-product-in-denied-category',
                'simple-product-in-denied-category',
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
     * And a bundle product is created in "Allowed Category" with default and allowed option products
     * And a bundle product is created in "Allowed Category" with default and denied option products
     * And a bundle product is created in "Denied Category" with default and denied option products
     * When a customer searches using a term shared by all products
     * Then products and options in allowed category are returned in the response
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
            BundleOptionFixture::class,
            [
                'title' => 'allowed_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_allowed_category$',
                ]
            ],
            'bundle_product_in_allowed_category_bundle_option'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'allowed_category_bundle_denied_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category$',
                ]
            ],
            'bundle_product_in_allowed_category_bundle_option_in_denied_category'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'denied_category_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category$',
                ]
            ],
            'bundle_product_in_denied_category_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Allowed Category',
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Allowed Category with denied option',
                'sku' => 'bundle-product-in-allowed-category-with-denied-option',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option_in_denied_category$'],
            ],
            'bundle_product_in_allowed_category_with_denied_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Denied Category',
                'sku' => 'bundle-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
                '_options' => ['$bundle_product_in_denied_category_bundle_option$'],
            ],
            'bundle_product_in_denied_category'
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
    items {
      name
      sku
      ... on BundleProduct {
        items {
          options {
            uid
            quantity
            position
            is_default
            price
            price_type
            can_change_quantity
            label
            product {
              name
              sku
            }
          }
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

        $this->assertEquals(4, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'bundle-product-in-allowed-category',
                'bundle-product-in-allowed-category-with-denied-option',
                'simple-product-in-allowed-category',
                'simple',
            ],
            array_column($response['products']['items'], 'sku')
        );
        $bundleProductKey = 0;
        $bundleProductDenyKey = 1;
        if (in_array('bundle-product-in-allowed-category', array_column(
            $response['products']['items'],
            'sku'
        )) !== false) {
            $bundleProductKey = array_search('bundle-product-in-allowed-category', array_column(
                $response['products']['items'],
                'sku'
            ));
        }
        $bundleProduct = $response['products']['items'][$bundleProductKey];
        $this->assertEquals(
            'bundle-product-in-allowed-category',
            $bundleProduct['sku']
        );
        $this->assertEquals(
            'Simple Product in Default Category',
            $bundleProduct['items'][0]['options'][0]['label']
        );
        $this->assertEquals(
            'simple',
            $bundleProduct['items'][0]['options'][0]['product']['sku']
        );
        $this->assertEquals(
            'Simple Product in Allowed Category',
            $bundleProduct['items'][0]['options'][1]['label']
        );
        $this->assertEquals(
            'simple-product-in-allowed-category',
            $bundleProduct['items'][0]['options'][1]['product']['sku']
        );
        if (in_array('bundle-product-in-allowed-category-with-denied-option', array_column(
            $response['products']['items'],
            'sku'
        )) !== false) {
            $bundleProductDenyKey = array_search('bundle-product-in-allowed-category-with-denied-option', array_column(
                $response['products']['items'],
                'sku'
            ));
        }
        $bundleProductWithDeniedOption = $response['products']['items'][$bundleProductDenyKey];
        $this->assertEquals(
            'bundle-product-in-allowed-category-with-denied-option',
            $bundleProductWithDeniedOption['sku']
        );
        $this->assertEquals(
            'Simple Product in Default Category',
            $bundleProductWithDeniedOption['items'][0]['options'][0]['label']
        );
        $this->assertEquals(
            'simple',
            $bundleProductWithDeniedOption['items'][0]['options'][0]['product']['sku']
        );
        $this->assertEquals(
            null,
            $bundleProductWithDeniedOption['items'][0]['options'][1]['label']
        );
        $this->assertEquals(
            null,
            $bundleProductWithDeniedOption['items'][0]['options'][1]['product']
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And category "Denied category for guests" is created
     * And a permission is applied to "Denied category for guests" revoking all permissions to guests
     * And a permission is applied to "Denied category for guests" allowing all permissions to logged in customer group
     * And a bundle product is created in "Denied category for guests"
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
            BundleOptionFixture::class,
            [
                'title' => 'denied_category_for_guests_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category_for_guests$',
                ]
            ],
            'bundle_product_in_denied_category_for_guests_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Denied Category',
                'sku' => 'bundle-product-in-denied-category-for-guests',
                'category_ids' => ['$denied_category_for_guests.id$'],
                '_options' => ['$bundle_product_in_denied_category_for_guests_bundle_option$'],
            ],
            'bundle_product_in_denied_category_for_guests'
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
     * And a bundle product is created in "Denied category for guests"
     * When a logged in customer searches for products within the category
     * Then the bundle product is returned in the response
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
            BundleOptionFixture::class,
            [
                'title' => 'denied_category_for_guests_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                    '$simple_product_in_denied_category_for_guests$',
                ]
            ],
            'bundle_product_in_denied_category_for_guests_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'name' => 'Bundle Product in Denied Category',
                'sku' => 'bundle-product-in-denied-category-for-guests',
                'category_ids' => ['$denied_category_for_guests.id$'],
                '_options' => ['$bundle_product_in_denied_category_for_guests_bundle_option$'],
            ],
            'bundle_product_in_denied_category_for_guests'
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
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );

        $this->assertEquals(2, $response['products']['total_count']);
        $this->assertEqualsCanonicalizing(
            [
                'simple-product-in-denied-category-for-guests',
                'bundle-product-in-denied-category-for-guests',
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
