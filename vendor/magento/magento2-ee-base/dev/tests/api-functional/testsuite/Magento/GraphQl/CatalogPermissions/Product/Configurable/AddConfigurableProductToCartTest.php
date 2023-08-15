<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Configurable;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Test\Fixture\Permission as PermissionFixture;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProductGraphQl\Model\Options\SelectionUidFormatter;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test adding configurable products via AddProductsToCart mutation with various
 * catalog permissions and customer groups
 */
class AddConfigurableProductToCartTest extends GraphQlAbstract
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
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var SelectionUidFormatter
     */
    private $selectionUidFormatter;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Configurable
     */
    private $productType;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->selectionUidFormatter = $this->objectManager->get(SelectionUidFormatter::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productType = $this->objectManager->get(Configurable::class);
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
     * "Allowed Category" grants all permissions on logged in customer group
     * "Denied Category" denies checkout permissions on logged in customer group and guests
     * A configurable product is assigned to "Denied Category"
     * Configurable option 1 is in allowed category and option 2 is in denied category.
     * When customer requests to add the product to the cart he gets the PERMISSION_DENIED error
     * even if the variation is in allowed category.
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @dataProvider variationsInAllowedAndDeniedCategoriesDataProvider()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(CategoryFixture::class, ['name' => 'Denied category'], 'denied_category'),
        DataFixture(AttributeFixture::class, as: 'attr'),
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
            ConfigurableProductFixture::class,
            [
                'name' => 'Configurable Product',
                'sku' => 'configurable-product-in-denied',
                'category_ids' => ['$denied_category.id$'],
                '_options' => ['$attr$'],
                '_links' => [
                    '$simple_product_in_allowed_category$',
                    '$simple_product_in_denied_category$'
                ]
            ],
            'configurable-product-in-denied'
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
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
    ]
    public function testConfigurableProductInDeniedCategory(
        int $optionIndex,
        string $expectedSimpleSku,
        array $customerData = []
    ) {
        $this->reindexCatalogPermissions();
        $configurableInfo = $this->getConfigurableProductInfo('Configurable');
        $attributeId = (int) $configurableInfo['configurable_options'][0]['attribute_id'];
        $valueIndex = $configurableInfo['configurable_options'][0]['values'][$optionIndex]['value_index'];

        $headerAuthorization = [];
        if (!empty($customerData)) {
            $headerAuthorization = $this->getCustomerAuthenticationHeader->execute(
                $customerData['email'],
                $customerData['password']
            );
            $cartId = $this->createEmptyCart($headerAuthorization);
        } else {
            $cartId = $this->createEmptyCart();
        }
        $mutation = $this->getAddProductToCartMutation(
            $cartId,
            $configurableInfo['sku'],
            1,
            $this->generateSuperAttributesUIDQuery($attributeId, $valueIndex)
        );
        $response = $this->graphQlMutation($mutation, [], '', $headerAuthorization);
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertNotEmpty($response['addProductsToCart']['user_errors']);
        $this->assertEquals(
            "You cannot add \"{$configurableInfo['sku']}\" to the cart.",
            $response['addProductsToCart']['user_errors'][0]['message']
        );
    }

    /**
     * Given Catalog Permissions are enabled
     * And 2 categories "Allowed Category" and "Denied Category" are created
     * And "Allowed Category" grants all permissions on logged in customer group
     * And "Denied Category" denies checkout permissions on logged in customer group and guests
     * A configurable product is assigned to both "Allowed Category" and "Denied Category"
     * Configurable option 1 is in allowed category and option 2 is in denied category.
     * When a logged in customer requests to add the product to the cart
     * Then the cart is populated with the requested product in both cases.
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @dataProvider variationsInAllowedAndDeniedCategoriesDataProvider()
     */
    #[
        DataFixture(CategoryFixture::class, ['name' => 'Allowed category'], 'allowed_category'),
        DataFixture(AttributeFixture::class, as: 'attr'),
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
            ConfigurableProductFixture::class,
            [
                'name' => 'Configurable Product',
                'sku' => 'configurable-product-in-both',
                'category_ids' => ['$denied_category.id$', '$allowed_category.id$'],
                '_options' => ['$attr$'],
                '_links' => [
                    '$simple_product_in_allowed_category$',
                    '$simple_product_in_denied_category$'
                ]
            ],
            'configurable-product-in-both'
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
                'customer_group_id' => 0, // NOT LOGGED IN (i.e. guest)
                'grant_catalog_category_view' => Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => Permission::PERMISSION_DENY,
            ]
        ),
    ]
    public function testConfigurableProductInAllowedCategory(
        int $optionIndex,
        string $expectedSimpleSku,
        array $customerData = []
    ) {
        $this->reindexCatalogPermissions();
        $configurableInfo = $this->getConfigurableProductInfo('Configurable');
        $attributeId = (int) $configurableInfo['configurable_options'][0]['attribute_id'];
        $valueIndex = $configurableInfo['configurable_options'][0]['values'][$optionIndex]['value_index'];
        $headerAuthorization = [];
        if (!empty($customerData)) {
            $headerAuthorization = $this->getCustomerAuthenticationHeader->execute(
                $customerData['email'],
                $customerData['password']
            );
            $cartId = $this->createEmptyCart($headerAuthorization);
        } else {
            $cartId = $this->createEmptyCart();
        }

        $mutation = $this->getAddProductToCartMutation(
            $cartId,
            $configurableInfo['sku'],
            1,
            $this->generateSuperAttributesUIDQuery($attributeId, $valueIndex)
        );
        $response = $this->graphQlMutation($mutation, [], '', $headerAuthorization);
        $configurableProductModel = $this->productRepository->get('configurable-product-in-both');

        $simpleVariation = $this->productType->getProductByAttributes(
            [$attributeId => $valueIndex],
            $configurableProductModel
        );
        $this->assertEquals($expectedSimpleSku, $simpleVariation->getSku());
        $this->assertCount(1, $response['addProductsToCart']['cart']['items']);
        $this->assertEquals(1, $response['addProductsToCart']['cart']['items'][0]['quantity']);
        $this->assertEquals(
            'configurable-product-in-both',
            $response['addProductsToCart']['cart']['items'][0]['product']['sku']
        );
        $this->assertEquals(
            $attributeId,
            $response['addProductsToCart']['cart']['items'][0]['configurable_options'][0]['id']
        );
        $this->assertEquals(
            $valueIndex,
            $response['addProductsToCart']['cart']['items'][0]['configurable_options'][0]['value_id']
        );
    }

    /**
     * Generates UID for super configurable product super attributes
     *
     * @param int $attributeId
     * @param int $valueIndex
     * @return string
     */
    private function generateSuperAttributesUIDQuery(int $attributeId, int $valueIndex): string
    {
        return 'selected_options: ["' . $this->selectionUidFormatter->encode($attributeId, $valueIndex) . '"]';
    }

    /**
     * Returns information about testable configurable product retrieved from GraphQl query
     *
     * @param string $searchTerm
     *
     * @return array
     */
    private function getConfigurableProductInfo(string $searchTerm): array
    {
        $searchResponse = $this->graphQlQuery($this->getFetchProductQuery($searchTerm));
        return current($searchResponse['products']['items']);
    }

    public function variationsInAllowedAndDeniedCategoriesDataProvider()
    {

        return [
            'variation in denied category, customer' => [
                'optionIndex' => 1, // simple_product_in_denied_category
                'expectedSimpleSku' => 'simple-product-in-denied-category',
                'customerData' => [
                    'email' => 'customer@example.com',
                    'password' => 'password'
                ],
            ],
            'variation in allowed category, customer' => [
                'optionIndex' => 0, // simple_product_in_allowed_category
                'expectedSimpleSku' => 'simple-product-in-allowed-category',
                'customerData' => [
                    'email' => 'customer@example.com',
                    'password' => 'password'
                ],
            ],
            'variation in denied category, guest' => [
                'optionIndex' => 1, // simple_product_in_denied_category
                'expectedSimpleSku' => 'simple-product-in-denied-category',
            ],
            'variation in allowed category, guest' => [
                'optionIndex' => 0, // simple_product_in_allowed_category
                'expectedSimpleSku' => 'simple-product-in-allowed-category',
            ],
        ];
    }

    /**
     * @param string $maskedQuoteId
     * @param string $configurableSku
     * @param int $quantity
     * @param string $selectedOptionsQuery
     * @return string
     */
    private function getAddProductToCartMutation(
        string $maskedQuoteId,
        string $configurableSku,
        int    $quantity,
        string $selectedOptionsQuery
    ): string {
        return <<<QUERY
mutation {
    addProductsToCart(
        cartId:"{$maskedQuoteId}"
        cartItems: [
            {
                sku: "{$configurableSku}"
                quantity: $quantity
                {$selectedOptionsQuery}
            }
        ]
    ) {
        cart {
            items {
                id
                uid
                quantity
                product {
                    sku
                    uid
                    id
                }
                ... on ConfigurableCartItem {
                    configurable_options {
                        id
                        configurable_product_option_uid
                        option_label
                        value_id
                        configurable_product_option_value_uid
                        value_label
                    }
                }
            }
        },
        user_errors {
            message
        }
    }
}
QUERY;
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
     * Returns GraphQl query for fetching configurable product information
     *
     * @param string $term
     * @return string
     */
    private function getFetchProductQuery(string $term): string
    {
        return <<<QUERY
{
  products(
    search:"{$term}"
    pageSize:1
  ) {
    items {
      sku
      uid
      ... on ConfigurableProduct {
        configurable_options {
          attribute_id
          attribute_uid
          attribute_code
          id
          uid
          label
          position
          product_id
          use_default
          values {
            uid
            default_label
            label
            store_label
            use_default_value
            value_index
          }
        }
        configurable_product_options_selection {
          options_available_for_selection {
            attribute_code
            option_value_uids
          }
          configurable_options {
            uid
            attribute_code
            label
            values {
              uid
              is_available
              is_use_default
              label
            }
          }
          variant {
            uid
            sku
            url_key
            url_path
          }
          media_gallery {
            url
            label
            disabled
          }
        }
      }
    }
  }
}
QUERY;
    }
}
