<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\GiftCard;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\GiftCard\Test\Fixture\GiftCard as GiftCardProductFixture;
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
 * Test adding gift card products via AddProductsToCart mutation with various catalog permissions and customer groups
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
     * And a giftcard product is assigned to "Allowed Category"
     * When a logged in customer requests to add the product to the cart
     * Then the cart is populated with the requested product
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-allowed-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$allowed_category.id$'],
            ],
            'giftcard_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-denied-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$denied_category.id$'],
            ],
            'giftcard_product_in_denied_category'
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

        /** @var ProductInterface $giftCardProductInAllowedCategory */
        $giftCardProductInAllowedCategory = $this->fixtures->get('giftcard_product_in_allowed_category');

        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $productResponse = $this->graphQlQuery(
            $this->getProductsQueryBySku($giftCardProductInAllowedCategory->getSku()),
            [],
            '',
            $headerAuthorization
        );

        $giftCardOptions = $productResponse['products']['items'][0]['gift_card_options'];
        $giftCardOptionValues = $this->getGiftCardOptionValues();

        $addToCartMutation = $this->getMutation(
            $cartId,
            $giftCardProductInAllowedCategory->getSku(),
            $desiredQuantity,
            1,
            $giftCardOptionValues['giftcard_sender_name'],
            $giftCardOptionValues['giftcard_sender_email'],
            $giftCardOptionValues['giftcard_recipient_name'],
            $giftCardOptionValues['giftcard_recipient_email'],
            $giftCardOptionValues['giftcard_message'],
            $this->mapGiftCardOptionTitleToUid($giftCardOptions)
        );

        $addToCartResponse = $this->graphQlMutation(
            $addToCartMutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertEmpty($addToCartResponse['addProductsToCart']['user_errors']);

        $cartItems = $addToCartResponse['addProductsToCart']['cart']['items'];
        $this->assertCount(1, $cartItems);

        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($giftCardProductInAllowedCategory->getSku(), $cartItems[0]['product']['sku']);
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And "Allowed Category" grants all permissions on guest customer group
     * And "Denied Category" revokes checkout permissions on guest customer group
     * And a giftcard product is assigned to "Allowed Category"
     * When a guest requests to add the product to the cart
     * Then the cart is populated with the requested product
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-allowed-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$allowed_category.id$'],
            ],
            'giftcard_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-denied-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$denied_category.id$'],
            ],
            'giftcard_product_in_denied_category'
        ),
        DataFixture(
            PermissionFixture::class,
            [
                'category_id' => '$allowed_category.id$',
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
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
    public function testProductThatIsAllowedToBeAddedToCartByGuest()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $giftCardProductInAllowedCategory */
        $giftCardProductInAllowedCategory = $this->fixtures->get('giftcard_product_in_allowed_category');

        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $productResponse = $this->graphQlQuery(
            $this->getProductsQueryBySku($giftCardProductInAllowedCategory->getSku())
        );

        $giftCardOptions = $productResponse['products']['items'][0]['gift_card_options'];
        $giftCardOptionValues = $this->getGiftCardOptionValues();

        $addToCartMutation = $this->getMutation(
            $cartId,
            $giftCardProductInAllowedCategory->getSku(),
            $desiredQuantity,
            1,
            $giftCardOptionValues['giftcard_sender_name'],
            $giftCardOptionValues['giftcard_sender_email'],
            $giftCardOptionValues['giftcard_recipient_name'],
            $giftCardOptionValues['giftcard_recipient_email'],
            $giftCardOptionValues['giftcard_message'],
            $this->mapGiftCardOptionTitleToUid($giftCardOptions)
        );

        $addToCartResponse = $this->graphQlMutation(
            $addToCartMutation
        );

        $this->assertEmpty($addToCartResponse['addProductsToCart']['user_errors']);

        $cartItems = $addToCartResponse['addProductsToCart']['cart']['items'];
        $this->assertCount(1, $cartItems);

        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($giftCardProductInAllowedCategory->getSku(), $cartItems[0]['product']['sku']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a giftcard product is assigned to a category that is denied from being checked out
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
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-allowed-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$allowed_category.id$'],
            ],
            'giftcard_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-denied-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$denied_category.id$'],
            ],
            'giftcard_product_in_denied_category'
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

        /** @var ProductInterface $giftCardProductInDeniedCategory */
        $giftCardProductInDeniedCategory = $this->fixtures->get('giftcard_product_in_denied_category');

        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $productResponse = $this->graphQlQuery(
            $this->getProductsQueryBySku($giftCardProductInDeniedCategory->getSku()),
            [],
            '',
            $headerAuthorization
        );

        $giftCardOptions = $productResponse['products']['items'][0]['gift_card_options'];
        $giftCardOptionValues = $this->getGiftCardOptionValues();

        $addToCartMutation = $this->getMutation(
            $cartId,
            $giftCardProductInDeniedCategory->getSku(),
            $desiredQuantity,
            1,
            $giftCardOptionValues['giftcard_sender_name'],
            $giftCardOptionValues['giftcard_sender_email'],
            $giftCardOptionValues['giftcard_recipient_name'],
            $giftCardOptionValues['giftcard_recipient_email'],
            $giftCardOptionValues['giftcard_message'],
            $this->mapGiftCardOptionTitleToUid($giftCardOptions)
        );

        $addToCartResponse = $this->graphQlMutation(
            $addToCartMutation,
            [],
            '',
            $headerAuthorization
        );

        $this->assertCount(
            1,
            $addToCartResponse['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $addToCartResponse['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $giftCardProductInDeniedCategory->getSku(),
            $addToCartResponse['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($addToCartResponse['addProductsToCart']['cart']['items']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * And a giftcard product is assigned to a category that is denied from being checked out
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
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-allowed-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$allowed_category.id$'],
            ],
            'giftcard_product_in_allowed_category'
        ),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-denied-category',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$denied_category.id$'],
            ],
            'giftcard_product_in_denied_category'
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

        /** @var ProductInterface $giftCardProductInDeniedCategory */
        $giftCardProductInDeniedCategory = $this->fixtures->get('giftcard_product_in_denied_category');

        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $productResponse = $this->graphQlQuery(
            $this->getProductsQueryBySku($giftCardProductInDeniedCategory->getSku())
        );

        $giftCardOptions = $productResponse['products']['items'][0]['gift_card_options'];
        $giftCardOptionValues = $this->getGiftCardOptionValues();

        $addToCartMutation = $this->getMutation(
            $cartId,
            $giftCardProductInDeniedCategory->getSku(),
            $desiredQuantity,
            1,
            $giftCardOptionValues['giftcard_sender_name'],
            $giftCardOptionValues['giftcard_sender_email'],
            $giftCardOptionValues['giftcard_recipient_name'],
            $giftCardOptionValues['giftcard_recipient_email'],
            $giftCardOptionValues['giftcard_message'],
            $this->mapGiftCardOptionTitleToUid($giftCardOptions)
        );

        $addToCartResponse = $this->graphQlMutation($addToCartMutation);

        $this->assertCount(
            1,
            $addToCartResponse['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $addToCartResponse['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $giftCardProductInDeniedCategory->getSku(),
            $addToCartResponse['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($addToCartResponse['addProductsToCart']['cart']['items']);
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Specified Customer Groups"
     * And "Customer Groups" is set to "General" (i.e. a logged in user)
     * And a giftcard product is assigned to a category that does not have any permissions applied
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
            GiftCardProductFixture::class,
            [
                'sku' => 'giftcard-product-in-category-without-permissions-applied',
                'open_amount_min' => 1,
                'open_amount_max' => 1,
                'category_ids' => ['$c2.id$'],
            ],
            'giftcard_product_in_category_without_permissions_applied'
        ),
    ]
    public function testProductThatIsDeniedFromBeingAddedToCartByGuestDueToGlobalConfiguration()
    {
        $this->reindexCatalogPermissions();

        /** @var ProductInterface $giftCardProduct */
        $giftCardProduct = $this->fixtures->get('giftcard_product_in_category_without_permissions_applied');
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $productResponse = $this->graphQlQuery(
            $this->getProductsQueryBySku($giftCardProduct->getSku())
        );

        $giftCardOptions = $productResponse['products']['items'][0]['gift_card_options'];
        $giftCardOptionValues = $this->getGiftCardOptionValues();

        $addToCartMutation = $this->getMutation(
            $cartId,
            $giftCardProduct->getSku(),
            $desiredQuantity,
            1,
            $giftCardOptionValues['giftcard_sender_name'],
            $giftCardOptionValues['giftcard_sender_email'],
            $giftCardOptionValues['giftcard_recipient_name'],
            $giftCardOptionValues['giftcard_recipient_email'],
            $giftCardOptionValues['giftcard_message'],
            $this->mapGiftCardOptionTitleToUid($giftCardOptions)
        );

        $addToCartResponse = $this->graphQlMutation($addToCartMutation);

        $this->assertCount(
            1,
            $addToCartResponse['addProductsToCart']['user_errors']
        );
        $this->assertEquals(
            'PERMISSION_DENIED',
            $addToCartResponse['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $giftCardProduct->getSku(),
            $addToCartResponse['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($addToCartResponse['addProductsToCart']['cart']['items']);
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
        $addToCartResponse = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->cartId = $addToCartResponse['createEmptyCart'];

        return $this->cartId;
    }

    /**
     * Get GraphQL query string for products by SKU
     * This is used in tests to get the encoded UIDs for gift card options
     *
     * @param string $sku
     * @return string
     */
    private function getProductsQueryBySku(string $sku): string
    {
        return <<<QUERY
{
  products(filter: {sku: {eq: "{$sku}"}}) {
    items {
      sku
      ... on GiftCardProduct {
        allow_open_amount
        open_amount_min
        open_amount_max
        giftcard_type
        is_redeemable
        lifetime
        allow_message
        message_max_length
        giftcard_amounts {
          uid
          value_id
          website_id
          value
          attribute_id
          website_value
        }
        gift_card_options {
          title
          required
          uid
          ... on CustomizableFieldOption {
            value: value {
              uid
            }
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Get addProductsToCart mutation based on passed parameters
     *
     * @param string $cartId
     * @param string $sku
     * @param int $quantity
     * @param float $customAmountValue
     * @param string $senderName
     * @param string $senderEmail
     * @param string $recipientName
     * @param string $recipientEmail
     * @param string $message
     * @param array $uidMap
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function getMutation(
        string $cartId,
        string $sku,
        int $quantity,
        float $customAmountValue,
        string $senderName,
        string $senderEmail,
        string $recipientName,
        string $recipientEmail,
        string $message,
        array $uidMap
    ): string {
        return <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "$cartId",
    cartItems: [
      {
        sku: "$sku"
        quantity: $quantity
        entered_options: [{
          uid: "{$uidMap['Custom Giftcard Amount']}"
      	  value: "{$customAmountValue}"
        }, {
          uid: "{$uidMap['Sender Name']}"
          value: "{$senderName}"
        }, {
          uid: "{$uidMap['Sender Email']}"
          value: "{$senderEmail}"
      	}, {
      	  uid: "{$uidMap['Recipient Name']}"
          value: "{$recipientName}"
      	}, {
          uid: "{$uidMap['Recipient Email']}"
          value: "{$recipientEmail}"
        }, {
      	  uid: "{$uidMap['Message']}"
          value: "{$message}"
      	}]
      }
    ]
  ) {
    cart {
      items {
        quantity
        product {
          sku
          stock_status
          name
        }
        ... on GiftCardCartItem {
          sender_name
          sender_email
          recipient_name
          recipient_email
          message
          amount {
            value
            currency
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
     * Return map of gift card option title to uid from $giftCardOptions
     *
     * @param array $giftCardOptions
     * @return array
     */
    private function mapGiftCardOptionTitleToUid(array $giftCardOptions): array
    {
        $uidMap = [];

        foreach ($giftCardOptions as $giftCardOption) {
            $uidMap[$giftCardOption['title']] = $giftCardOption['uid'];
        }

        return $uidMap;
    }

    /**
     * Get gift card option values to be used in addProductsToCart mutation
     *
     * @return string[]
     */
    private function getGiftCardOptionValues(): array
    {
        return [
            'giftcard_sender_name' => 'Sender 1',
            'giftcard_sender_email' => 'sender1@email.com',
            'giftcard_recipient_name' => 'Recipient 1',
            'giftcard_recipient_email' => 'recipient1@email.com',
            'giftcard_message' => 'Message 1',
        ];
    }
}
