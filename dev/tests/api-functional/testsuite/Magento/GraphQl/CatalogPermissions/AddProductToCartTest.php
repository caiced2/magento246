<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Test products that are not allowed to be added to cart
 */
class AddProductToCartTest extends GraphQlAbstract
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
     * 'simple_allow_12' product is in catalog with `allow regular customer checkout' permission
     * Regular customer add `simple_allow_12` product to cart successfully when catalog permission is on
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsAddedToCart()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple_allow_122';
        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);
        $queryCartItems[] = ['sku' => $productSku, 'quantity' => $desiredQuantity];
        $query = $this->getQuery($cartId, $queryCartItems);

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($productSku, $cartItems[0]['product']['sku']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(0, $userErrors);
    }

    /**
     * 'simple_allow_12' product is in catalog with `allow regular customer checkout' permission
     * 'simple_deny_122' product is in catalog with `deny regular customer checkout' permission
     * Regular customer adds `simple_allow_12` product to cart successfully
     * and fails adding 'simple_deny_122' product to cart when catalog permission is on
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsAddedAndDeniedToCart()
    {
        $this->reindexCatalogPermissions();

        $productSkuA = 'simple_allow_122';
        $desiredQuantity = 5;
        $productSkuB = 'simple_deny_122';
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);
        $queryCartItems[] = ['sku' => $productSkuA, 'quantity' => $desiredQuantity];
        $queryCartItems[] = ['sku' => $productSkuB, 'quantity' => $desiredQuantity];

        $query = $this->getQuery($cartId, $queryCartItems);

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertEquals(1, count($cartItems));
        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($productSkuA, $cartItems[0]['product']['sku']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(1, $userErrors);
        self::assertStringContainsString($productSkuB, $userErrors[0]['message']);
        self::assertEquals('PERMISSION_DENIED', $userErrors[0]['code']);
    }

    /**
     * 'simple_deny_122' product is in catalog with `deny regular customer checkout' permission
     * Regular customer fails adding 'simple_deny_122' product to cart when catalog permission is on
     * and grant checkout items store configuration is on
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsDeniedToCartForCustomer()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple_deny_122';
        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);
        $queryCartItems[] = ['sku' => $productSku, 'quantity' => $desiredQuantity];
        $query = $this->getQuery($cartId, $queryCartItems);

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(1, $userErrors);
        self::assertStringContainsString($productSku, $userErrors[0]['message']);
        self::assertEquals('PERMISSION_DENIED', $userErrors[0]['code']);
    }

    /**
     * 'simple_deny_122' product is in catalog with `deny guest checkout' permission
     * Guest fails adding 'simple_deny_122' product to cart when catalog permission is on
     * and grant checkout items store configuration is on
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsDeniedToCartForGuest()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple_deny_122';
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();
        $queryCartItems[] = ['sku' => $productSku, 'quantity' => $desiredQuantity];
        $query = $this->getQuery($cartId, $queryCartItems);

        $response = $this->graphQlMutation($query);

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(1, $userErrors);
        self::assertStringContainsString($productSku, $userErrors[0]['message']);
        self::assertEquals('PERMISSION_DENIED', $userErrors[0]['code']);
    }

    /**
     * `simple333` product is in a catalog without catalog permission
     * Grant customer group 1 permission to checkout items in configuration
     * Customer group 1 customer adds 'simple333` product to cart successfully
     * Guest fails adding `simple333` product to cart.
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items_groups 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products.php
     * @dataProvider productToCartGrantCheckoutItemsPermissionByConfigurationDataProvider
     * @param bool $isGuest
     * @param array $expectedResult
     * @return void
     * @throws \Exception
     */
    public function testGrantCheckoutItemsPermissionByConfiguration($isGuest, $expectedResult)
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple333';
        $desiredQuantity = 5;
        $headerAuthorization = null;
        if (!$isGuest) {
            $currentEmail = 'customer@example.com';
            $currentPassword = 'password';
            $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
                ->execute($currentEmail, $currentPassword);
        }

        $cartId = $this->createEmptyCart($headerAuthorization);
        $queryCartItems[] = ['sku' => $productSku, 'quantity' => $desiredQuantity];
        $query = $this->getQuery($cartId, $queryCartItems);

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization ?: []
        );

        $this->removeQuote($cartId);

        $this->assertEquals($expectedResult, $response['addProductsToCart']);
    }

    public function productToCartGrantCheckoutItemsPermissionByConfigurationDataProvider()
    {
        return [
            'guest-denied' => [
                'isGuest' => true,
                'expectedResult' => [
                    'cart' => [
                        'items' => [],
                    ],
                    'user_errors' => [
                        [
                            'code' => 'PERMISSION_DENIED',
                            'message' => 'You cannot add "simple333" to the cart.'
                        ]
                    ]
                ]
            ],
            'customer-allowed' => [
                'isGuest' => false,
                'expectedResult' => [
                    'cart' => [
                        'items' => [
                            [
                                'quantity' => 5,
                                'product' => [
                                    'sku' => 'simple333'
                                ]
                            ]
                        ]
                    ],
                    'user_errors' => []
                ]
            ]
        ];
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "NO"
     * And a product with no category is added to cart for customer
     * When a logged in customer requests to add the product to the cart
     * Then the cart has user errors shown
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 0
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *
     * Test for AC-6050 bug
     */
    public function testProductWhenAllowAddToCartDisAllowedForCustomer()
    {
        $this->reindexCatalogPermissions();
        $productSku = 'simple_product';
        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);
        $mutation = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }

      ]
  ) {
  user_errors{
      code
      message
    }
    cart {
    total_quantity
      items {
       quantity
       product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );
        $this->assertNotEmpty($response['addProductsToCart']['user_errors']);
        $this->assertEquals(
            0,
            $response["addProductsToCart"]["cart"]["total_quantity"]
        );
        $this->assertEquals(
            "PERMISSION_DENIED",
            $response["addProductsToCart"]["user_errors"][0]["code"]
        );
        $this->assertEquals(
            'You cannot add "simple_product" to the cart.',
            $response["addProductsToCart"]["user_errors"][0]["message"]
        );
    }

    /**
     * Given Catalog permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "NO"
     * And a product with no category is added to cart for customer
     * When a logged in customer requests to add the product to the cart
     * Then the cart has user errors shown
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 0
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     *
     * Test for AC-6050 bug
     */
    public function testProductWhenAllowAddToCartDisAllowedForGuest()
    {
        $this->reindexCatalogPermissions();
        $productSku = 'simple_product';
        $desiredQuantity = 5;
        $cartId = $this->createEmptyCart();
        $mutation = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }

      ]
  ) {
  user_errors{
      code
      message
    }
    cart {
    total_quantity
      items {
       quantity
       product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            ''
        );
        $this->assertNotEmpty($response['addProductsToCart']['user_errors']);
        $this->assertEquals(
            0,
            $response["addProductsToCart"]["cart"]["total_quantity"]
        );
        $this->assertEquals(
            "PERMISSION_DENIED",
            $response["addProductsToCart"]["user_errors"][0]["code"]
        );
        $this->assertEquals(
            'You cannot add "simple_product" to the cart.',
            $response["addProductsToCart"]["user_errors"][0]["message"]
        );
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
    }

    /**
     * Create empty cart
     *
     * @param array|null $headerAuthorization
     * @return string
     * @throws \Exception
     */
    private function createEmptyCart(array $headerAuthorization = null): string
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
            $headerAuthorization ?? []
        );
        $cartId = $response['createEmptyCart'];
        return $cartId;
    }

    /**
     * Remove the quote
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
     * @param string $cartId
     * @param array $queryCartItems
     * @return string
     */
    private function getQuery(string $cartId, array $queryCartItems): string
    {
        $cartItems ='';
        foreach ($queryCartItems as $cartItem) {
            $productSku = $cartItem['sku'];
            $desiredQuantity = $cartItem['quantity'];
            $cartItems .= "{
          sku: \"{$productSku}\"
          quantity: {$desiredQuantity}
      },";
        }

        return <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {$cartItems}
    ]
  ) {
    cart {
      items {
       quantity
       product {
          sku
        }
      }
    },
    user_errors {
        code,
        message
    }
  }
}
MUTATION;
    }
}
