<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Bundle;

use Magento\Bundle\Model\Option as BundleOption;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\CatalogPermissions\Model\Permission;

/**
 * Test adding bundle products via AddProductsToCart mutation with various catalog permissions and customer groups
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

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    protected function tearDown(): void
    {
        if ($this->cartId) {
            $this->removeQuote($this->cartId);
        }

        parent::tearDown();
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And "Allowed Category" grants all permissions on logged in customer group
     * And "Denied Category" revokes checkout permissions on logged in customer group
     * And a bundle product is assigned to a "Allowed Category"
     * When a logged in customer requests to add the product to the cart
     * Then the cart is populated with the requested product
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
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
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'simple_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            ProductFixture::class,
            [
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
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
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

        /** @var ProductInterface $bundleProductInAllowedCategory */
        $bundleProductInAllowedCategory = $this->fixtures->get('bundle_product_in_allowed_category');

        $selectedOptions = $this->getSelectedOptionsForBundleProductBySelectedProductSkus(
            $bundleProductInAllowedCategory,
            [
                'simple',
                'simple-product-in-allowed-category',
            ]
        );

        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $mutation = $this->getMutation(
            $cartId,
            $bundleProductInAllowedCategory->getSku(),
            $desiredQuantity,
            $selectedOptions
        );

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertEmpty($response['addProductsToCart']['user_errors']);

        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertCount(1, $cartItems);

        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($bundleProductInAllowedCategory->getSku(), $cartItems[0]['product']['sku']);

        $this->assertCount(1, $cartItems[0]['bundle_options']);
        $this->assertCount(2, $cartItems[0]['bundle_options'][0]['values']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a bundle product is assigned to a category that is denied from being checked out
     * by both a guest and a logged in customer
     * When a logged in customer requests to add the product to the cart
     * Then the cart is empty
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
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'simple_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            ProductFixture::class,
            [
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
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
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

        /** @var ProductInterface $bundleProductInDeniedCategory */
        $bundleProductInDeniedCategory = $this->fixtures->get('bundle_product_in_denied_category');

        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $selectedOptionsPermutations = [
            ['simple'],
            ['simple-product-in-denied-category'],
            ['simple', 'simple-product-in-denied-category'],
        ];

        foreach ($selectedOptionsPermutations as $selectedOptionsPermutation) {
            $selectedOptions = $this->getSelectedOptionsForBundleProductBySelectedProductSkus(
                $bundleProductInDeniedCategory,
                $selectedOptionsPermutation
            );

            $mutation = $this->getMutation(
                $cartId,
                $bundleProductInDeniedCategory->getSku(),
                $desiredQuantity,
                $selectedOptions
            );

            $response = $this->graphQlMutation(
                $mutation,
                [],
                '',
                $headerAuthorization
            );

            $failureMessage = 'Failed for $selectedOptionsPermutation ' . implode(', ', $selectedOptionsPermutation);

            $this->assertCount(
                1,
                $response['addProductsToCart']['user_errors'],
                $failureMessage
            );
            $this->assertEquals(
                'PERMISSION_DENIED',
                $response['addProductsToCart']['user_errors'][0]['code']
            );
            $this->assertStringContainsString(
                $bundleProductInDeniedCategory->getSku(),
                $response['addProductsToCart']['user_errors'][0]['message']
            );
            $this->assertEmpty(
                $response['addProductsToCart']['cart']['items'],
                $failureMessage
            );
        }
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a bundle product is assigned to a category that is denied from being checked out
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
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'simple_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            ProductFixture::class,
            [
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
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
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

        /** @var ProductInterface $bundleProductInDeniedCategory */
        $bundleProductInDeniedCategory = $this->fixtures->get('bundle_product_in_denied_category');

        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $selectedOptionsPermutations = [
            ['simple'],
            ['simple-product-in-denied-category'],
            ['simple', 'simple-product-in-denied-category'],
        ];

        foreach ($selectedOptionsPermutations as $selectedOptionsPermutation) {
            $selectedOptions = $this->getSelectedOptionsForBundleProductBySelectedProductSkus(
                $bundleProductInDeniedCategory,
                $selectedOptionsPermutation
            );

            $mutation = $this->getMutation(
                $cartId,
                $bundleProductInDeniedCategory->getSku(),
                $desiredQuantity,
                $selectedOptions
            );

            $response = $this->graphQlMutation($mutation);

            $failureMessage = 'Failed for $selectedOptionsPermutation ' . implode(', ', $selectedOptionsPermutation);

            $this->assertCount(
                1,
                $response['addProductsToCart']['user_errors'],
                $failureMessage
            );
            $this->assertEquals(
                'PERMISSION_DENIED',
                $response['addProductsToCart']['user_errors'][0]['code']
            );
            $this->assertStringContainsString(
                $bundleProductInDeniedCategory->getSku(),
                $response['addProductsToCart']['user_errors'][0]['message']
            );
            $this->assertEmpty(
                $response['addProductsToCart']['cart']['items'],
                $failureMessage
            );
        }
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "General" (i.e. a logged in user)
     * And a bundle product is assigned to a category that does not have any permissions applied
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
            BundleOptionFixture::class,
            [
                'title' => 'category_with_no_permissions_bundle_option',
                'type' => 'checkbox',
                'product_links' => [
                    '$simple_product_in_default_category$',
                ]
            ],
            'bundle_product_in_category_without_permissions_bundle_option'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'sku' => 'bundle-product-in-category-without-permissions-applied',
                'category_ids' => ['$c2.id$'],
                '_options' => ['$bundle_product_in_category_without_permissions_bundle_option$'],
            ],
            'bundle_product_in_category_without_permissions_applied'
        ),
    ]
    public function testProductThatIsDeniedFromBeingAddedToCartByGuestDueToGlobalConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $bundleProduct */
        $bundleProduct = $this->fixtures->get('bundle_product_in_category_without_permissions_applied');
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $selectedOptions = $this->getSelectedOptionsForBundleProductBySelectedProductSkus(
            $bundleProduct,
            ['simple']
        );

        $mutation = $this->getMutation(
            $cartId,
            $bundleProduct->getSku(),
            $desiredQuantity,
            $selectedOptions
        );

        $response = $this->graphQlMutation($mutation);

        $this->assertCount(
            1,
            $response['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $bundleProduct->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a bundle product is assigned to a category that is allowed to be checked out by a logged in customer
     * And the bundle product contains a simple product assigned to a category that is denied from being checked out
     * by a logged in customer
     * When a logged in customer requests to add the bundle product to the cart with the denied simple product option
     * Then the cart is populated with the requested product
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
                'sku' => 'simple-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
            ],
            'simple_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            ProductFixture::class,
            [
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
                    '$simple_product_in_denied_category$' // here is denied product reference in allowed category option
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
                'sku' => 'bundle-product-in-allowed-category',
                'category_ids' => ['$allowed_category.id$'],
                '_options' => ['$bundle_product_in_allowed_category_bundle_option$'],
            ],
            'bundle_product_in_allowed_category'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
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
    public function testBundleProductInAllowedCategoryWithSimpleProductOptionInDeniedCategory()
    {
        /** @var ProductInterface $bundleProductInAllowedCategory */
        $bundleProductInAllowedCategory = $this->fixtures->get('bundle_product_in_allowed_category');

        $desiredQuantity = 5;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $selectedOptions = $this->getSelectedOptionsForBundleProductBySelectedProductSkus(
            $bundleProductInAllowedCategory,
            ['simple-product-in-denied-category']
        );

        $mutation = $this->getMutation(
            $cartId,
            $bundleProductInAllowedCategory->getSku(),
            $desiredQuantity,
            $selectedOptions
        );

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertEmpty($response['addProductsToCart']['user_errors']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertCount(1, $cartItems);

        $this->assertCount(1, $cartItems[0]['bundle_options']);
        $this->assertCount(1, $cartItems[0]['bundle_options'][0]['values']);
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
     * @param array $selectedOptions - array of base64-encoded bundle options
     * @return string
     */
    private function getMutation(
        string $cartId,
        string $sku,
        int $quantity,
        array $selectedOptions = []
    ): string {
        $selectedOptionsListString = implode(PHP_EOL, array_map(function ($selectedOption) {
            return sprintf('"%s"', $selectedOption);
        }, $selectedOptions));

        return <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "$cartId",
    cartItems: [
      {
        sku: "$sku"
        quantity: $quantity
        selected_options: [
            $selectedOptionsListString
        ]
      }
    ]
  ) {
    cart {
      items {
        quantity
        product {
          sku
        }
        ... on BundleCartItem {
          bundle_options {
            uid
            label
            type
            values {
              id
              label
              price
              quantity
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

    /**
     * Get base64-encoded selected product link ids/selection ids based on $selectedProductSkus for $bundleProductSku
     * Bundle products used in thse tests contain one option, which themselves contain
     * a simple product in "Default Category" and possibly a simple product
     * in either "Allowed Category" or "Denied Category", depending on the Bundle product.
     *
     * @param ProductInterface $bundleProduct
     * @param array $selectedProductSkusRequested
     * @return array
     */
    private function getSelectedOptionsForBundleProductBySelectedProductSkus(
        ProductInterface $bundleProduct,
        array $selectedProductSkusRequested
    ): array {
        $bundleProductOptions = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();

        $selectedOptions = [];

        $selectedProductSkusAdded = [];

        /** @var $bundleProductOption BundleOption */
        foreach ($bundleProductOptions as $bundleProductOption) {
            $optionId = $bundleProductOption->getOptionId();
            foreach ($bundleProductOption->getProductLinks() as $productLink) {
                if (!in_array($productLink->getSku(), $selectedProductSkusRequested)) {
                    continue;
                }

                $selectionId = $productLink->getId();
                $selectedOptions[] = "bundle/$optionId/$selectionId/1";

                $selectedProductSkusAdded[] = $productLink->getSku();
            }
        }

        // fail if SKU in $selectedProductSkusRequested wasn't added
        if (count($selectedProductSkusAdded) !== count($selectedProductSkusRequested)) {
            $this->fail(
                'Not all of $selectedProductSkusRequested have been added; missing SKUs: ' .
                implode(', ', array_diff($selectedProductSkusRequested, $selectedProductSkusAdded))
            );
        }

        return array_map('base64_encode', $selectedOptions);
    }
}
