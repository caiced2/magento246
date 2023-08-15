<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Virtual;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Virtual as VirtualProductFixture;
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
 * Test adding virtual products via AddProductsToCart mutation with various catalog permissions and customer groups
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
     * And a virtual product is assigned to "Allowed Category"
     * When a logged in customer requests to add the product to the cart
     * Then the cart is populated with the requested product
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

        /** @var ProductInterface $virtualProductInAllowedCategory */
        $virtualProductInAllowedCategory = $this->fixtures->get('virtual_product_in_allowed_category');

        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $mutation = $this->getMutation(
            $cartId,
            $virtualProductInAllowedCategory->getSku(),
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
        $this->assertCount(1, $cartItems);

        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($virtualProductInAllowedCategory->getSku(), $cartItems[0]['product']['sku']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a virtual product is assigned to a category that is denied from being checked out
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

        /** @var ProductInterface $virtualProductInDeniedCategory */
        $virtualProductInDeniedCategory = $this->fixtures->get('virtual_product_in_denied_category');

        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $mutation = $this->getMutation(
            $cartId,
            $virtualProductInDeniedCategory->getSku(),
            $desiredQuantity
        );

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertCount(
            1,
            $response['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $virtualProductInDeniedCategory->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a virtual product is assigned to a category that is denied from being checked out
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

        /** @var ProductInterface $virtualProductInDeniedCategory */
        $virtualProductInDeniedCategory = $this->fixtures->get('virtual_product_in_denied_category');

        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $mutation = $this->getMutation(
            $cartId,
            $virtualProductInDeniedCategory->getSku(),
            $desiredQuantity
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
            $virtualProductInDeniedCategory->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "General" (i.e. a logged in user)
     * And a virtual product is assigned to a category that does not have any permissions applied
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
    public function testProductThatIsDeniedFromBeingAddedToCartByGuestDueToGlobalConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $virtualProduct */
        $virtualProduct = $this->fixtures->get('virtual_product_in_category_without_permissions_applied');
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $mutation = $this->getMutation(
            $cartId,
            $virtualProduct->getSku(),
            $desiredQuantity
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
            $virtualProduct->getSku(),
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
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
          ... on VirtualProduct {
            sku
            stock_status
            name
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
