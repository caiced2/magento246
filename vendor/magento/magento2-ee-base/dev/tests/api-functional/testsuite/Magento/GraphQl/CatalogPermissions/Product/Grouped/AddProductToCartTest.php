<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Grouped;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Catalog\Test\Fixture\Virtual as VirtualProductFixture;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test adding grouped products via AddProductsToCart mutation with various
 * catalog permissions and customer groups
 */
class AddProductToCartTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Masked ID of cart created in createEmptyCartMutation
     *
     * @var string
     */
    private $cartId;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    protected function tearDown(): void
    {
        if ($this->cartId) {
            $this->removeQuote($this->cartId);
        }

        parent::tearDown();
    }

    /**
     * _security
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And "Allowed Category" grants all permissions on logged in customer group
     * And "Denied Category" revokes checkout permissions on logged in customer group
     * And a grouped product is assigned to "Allowed Category"
     * When a logged in customer requests to add the product to the cart
     * Then the cart is populated with the requested product
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
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
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_allowed_category.sku$', 'qty' => 12]
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
                    ['sku' => '$simple_product_in_denied_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 12]
                ],
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
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        )
    ]
    public function testProductThatIsAllowedToBeAddedToCartByCustomer()
    {
        $this->reindexCatalogPermissions();
        /** @var ProductInterface $groupedProductInAllowedCategory */
        $groupedProductInAllowedCategory = $this->fixtures->get('grouped-product-allowed');
        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $mutation = $this->getMutation(
            $cartId,
            $groupedProductInAllowedCategory->getSku(),
            $desiredQuantity
        );

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertEmpty($response['addProductsToCart']['user_errors']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertCount(2, $cartItems);
    }

    /**
     * _security
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a grouped product is assigned to a category that is denied from being checked out
     * by both a guest and a logged in customer
     * When a logged in customer requests to add the product to the cart
     * Then the cart is empty
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *

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
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_allowed_category.sku$', 'qty' => 12]
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
                    ['sku' => '$simple_product_in_denied_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 12]
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
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        )
    ]
    public function testProductThatIsDeniedFromBeingAddedToCartByCustomer()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $groupedProductInDeniedCategory */
        $groupedProductInDeniedCategory = $this->fixtures->get('grouped-product-denied');
        $desiredQuantity = 5;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization =  $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $mutation = $this->getMutation(
            $cartId,
            $groupedProductInDeniedCategory->getSku(),
            $desiredQuantity
        );

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertCount(
            1,
            $response['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $groupedProductInDeniedCategory->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a grouped product is assigned to a category that is denied from being checked out
     * by both a guest and a logged in customer
     * When a guest requests to add the product to the cart
     * Then the cart is empty
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
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
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_allowed_category.sku$', 'qty' => 12]
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
                    ['sku' => '$simple_product_in_denied_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 12]
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
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        )
    ]
    public function testProductThatIsDeniedFromBeingAddedToCartByGuest()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $groupedProductInDeniedCategory */
        $groupedProductInDeniedCategory = $this->fixtures->get('grouped-product-denied');
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $mutation = $this->getMutation(
            $cartId,
            $groupedProductInDeniedCategory->getSku(),
            $desiredQuantity
        );
        $response = $this->graphQlMutation($mutation);
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertCount(
            1,
            $response['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $groupedProductInDeniedCategory->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "General" (i.e. a logged in user)
     * And a grouped product is assigned to a category that does not have any permissions applied
     * When a guest requests to add the product to the cart
     * Then the cart is empty
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items_groups 1
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
    public function testProductThatIsDeniedFromBeingAddedToCartByGuestDueToGlobalConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $groupedProduct */
        $groupedProduct = $this->fixtures->get('grouped-product-without-permissions-applied');
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $mutation = $this->getMutation(
            $cartId,
            $groupedProduct->getSku(),
            $desiredQuantity,
        );

        $response = $this->graphQlMutation($mutation);
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);

        $this->assertCount(
            1,
            $response['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $groupedProduct->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a grouped product is assigned to a category that is allowed to be checked out by a logged in customer
     * And the grouped product contains an virtual product assigned to a category that is denied from being checked out
     * by a logged in customer
     * When a logged in customer requests to add the grouped product
     * to the cart with the denied virtual product option
     * Then the cart is populated with only the allowed products
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
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
                'sku' => 'grouped-product-allowed-denied-virtual-option',
                'category_ids' => ['$allowed_category.id$'],
                'product_links' => [
                    ['sku' => '$simple_product_in_allowed_category.sku$', 'qty' => 12],
                    ['sku' => '$virtual_product_in_denied_category.sku$', 'qty' => 12]
                    // here is denied product reference in allowed category option
                ]
            ],
            'grouped-product-allowed-denied-virtual-option'
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
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$denied_category.id$',
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        )
    ]
    public function testGroupedProductInAllowedCategoryWithVirtualProductOptionInDeniedCategory()
    {
        $this->markTestSkipped('Test is skipped by issue AC-6872');
        $this->reindexCatalogPermissions();
        /** @var ProductInterface $groupedProduct */
        $groupedProduct = $this->fixtures->get('grouped-product-allowed-denied-virtual-option');
        $desiredQuantity = 5;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);
        $mutation = $this->getMutation(
            $cartId,
            $groupedProduct->getSku(),
            $desiredQuantity
        );

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertCount(
            1,
            $response['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );

        //TODO: determine the exact error message needs to
        // either individual product or the grouped product
        $this->assertStringContainsString(
            $groupedProduct->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertCount(1, $cartItems);
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

    /**
     * Create empty cart
     *
     * @param array $headerAuthorization
     * @return string
     * @throws \Exception
     */
    private function createEmptyCart(array $headerAuthorization = []): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->cartId = $response['createEmptyCart'];

        return $this->cartId;
    }

    /**
     * Get addProductsToCart mutation based on passed parameters
     *
     * @param string $cartId
     * @param string $sku
     * @param int $quantity
     * @return string
     */
    private function getMutation(
        string $cartId,
        string $sku,
        int $quantity
    ): string {
        return <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "$cartId",
    cartItems: [
      {
        sku: "$sku"
        quantity: $quantity
      }
    ]
  ) {
    cart {
      items {
        quantity
        product {
          sku
          name
          product_links{
              link_type
              linked_product_sku
              linked_product_type
              sku
              position
            }
  ... on GroupedProduct {
        items{
          qty
          position
          product{
            sku
            name
            product_links{
              link_type
              linked_product_sku
              linked_product_type
              sku
              position
            }
            url_key
          }
        }
      }
  }
      }
  }
  user_errors {
      code
      message
    }
  }
}
MUTATION;
    }

    /**
     * Remove the quote from the database
     *
     * @param string $maskedId
     */
    private function removeQuote(string $maskedId): void
    {
        $maskedIdToQuote = $this->objectManager->get(MaskedQuoteIdToQuoteIdInterface::class);
        $quoteId = $maskedIdToQuote->execute($maskedId);

        $cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $quote = $cartRepository->get($quoteId);
        $cartRepository->delete($quote);
    }
}
