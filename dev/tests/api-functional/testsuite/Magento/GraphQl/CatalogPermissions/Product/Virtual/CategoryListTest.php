<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Virtual;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Virtual as VirtualProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\CatalogPermissions\Model\Permission;

/**
 * Test presence/absence of virtual products via categoryList querying with
 * various catalog permissions and customer groups
 */
class CategoryListTest extends GraphQlAbstract
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
     * And a virtual product is created in "Allowed Category"
     * And a virtual product is created in "Denied Category"
     * When a guest queries for categories and their products
     * Then all categories and products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 0
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            VirtualProductFixture::class,
            [
                'sku' => 'virtual-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'virtual_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            VirtualProductFixture::class,
            [
                'sku' => 'virtual-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
            ],
            'virtual_product_in_denied_category'
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
  categoryList(filters: {name: {match: "category"}}) {
    id
    name
    product_count
    products {
      items {
        ... on VirtualProduct {
          sku
          stock_status
          name
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertCount(3, $response['categoryList']);

        list($defaultCategory, $allowedCategory, $deniedCategory) = $response['categoryList'];

        $this->assertEquals('Default Category', $defaultCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'virtual-product-in-allowed-category',
                'virtual-product-in-denied-category',
            ],
            array_column($defaultCategory['products']['items'], 'sku')
        );

        $this->assertEquals('Allowed category', $allowedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'virtual-product-in-allowed-category',
            ],
            array_column($allowedCategory['products']['items'], 'sku')
        );

        $this->assertEquals('Denied category', $deniedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'virtual-product-in-denied-category',
            ],
            array_column($deniedCategory['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a virtual product is created in "Allowed Category"
     * And a virtual product is created in "Denied Category"
     * When a guest queries for categories and their products
     * Then no categories or products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            VirtualProductFixture::class,
            [
                'sku' => 'virtual-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'virtual_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            VirtualProductFixture::class,
            [
                'sku' => 'virtual-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
            ],
            'virtual_product_in_denied_category'
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
  categoryList(filters: {name: {match: "category"}}) {
    id
    name
    product_count
    products {
      items {
        ... on VirtualProduct {
          sku
          stock_status
          name
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertCount(0, $response['categoryList']);
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And a permission is applied to "Allowed Category" granting all permissions to logged in customer group
     * And a permission is applied to "Denied Category" revoking all permissions to logged in customer group
     * And a virtual product is created in "Allowed Category"
     * And a virtual product is created in "Denied Category"
     * When a logged in customer queries for categories and their products
     * Then only "Allowed Category" and its products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            VirtualProductFixture::class,
            [
                'sku' => 'virtual-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'virtual_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            VirtualProductFixture::class,
            [
                'sku' => 'virtual-product-in-denied-category',
                'category_ids' => ['$denied_category.id$'],
            ],
            'virtual_product_in_denied_category'
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
  categoryList(filters: {name: {match: "category"}}) {
    id
    name
    product_count
    products {
      items {
        ... on VirtualProduct {
          sku
          stock_status
          name
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

        $this->assertCount(1, $response['categoryList']);
        $allowedCategory = $response['categoryList'][0];
        $this->assertEquals('Allowed category', $allowedCategory['name']);
        $this->assertEqualsCanonicalizing(
            [
                'virtual-product-in-allowed-category',
            ],
            array_column($allowedCategory['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "General" (i.e. a logged in user)
     * And a virtual product is assigned to a category that does not have any permissions applied
     * When a logged in customer queries for a category containing the virtual product
     * Then the category and its virtual product are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view_groups 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
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
                'sku' => 'virtual-product-in-category-without-permissions-applied',
                'category_ids' => ['$c2.id$'],
            ],
            'virtual_product_in_category_without_permissions_applied'
        ),
    ]
    public function testCategoriesReturnedForCustomerGroupsAllowedByConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var CategoryInterface $category2 */
        $category2 = $this->fixtures->get('c2');
        $category2Id = $category2->getId();

        $query = <<<QUERY
{
  categoryList(filters: {ids: {eq: "$category2Id"}}) {
    id
    name
    product_count
    products {
      items {
        ... on VirtualProduct {
          sku
          stock_status
          name
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

        $this->assertCount(1, $response['categoryList']);
        $this->assertEquals('Category 2', $response['categoryList'][0]['name']);
        $this->assertEqualsCanonicalizing(
            [
                'virtual-product-in-category-without-permissions-applied',
            ],
            array_column($response['categoryList'][0]['products']['items'], 'sku')
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "Wholesale"
     * And a virtual product is assigned to a category that does not have any permissions applied
     * When a logged in customer queries for a category containing the virtual product
     * Then no categories or products are returned in the response
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view_groups 2
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
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
                'sku' => 'virtual-product-in-category-without-permissions-applied',
                'category_ids' => ['$c2.id$'],
            ],
            'virtual_product_in_category_without_permissions_applied'
        ),
    ]
    public function testCategoriesReturnedForCustomerGroupsDeniedByConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var CategoryInterface $category2 */
        $category2 = $this->fixtures->get('c2');
        $category2Id = $category2->getId();

        $query = <<<QUERY
{
  categoryList(filters: {ids: {eq: "$category2Id"}}) {
    id
    name
    product_count
    products {
      items {
        ... on VirtualProduct {
          sku
          stock_status
          name
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

        $this->assertCount(0, $response['categoryList']);
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
