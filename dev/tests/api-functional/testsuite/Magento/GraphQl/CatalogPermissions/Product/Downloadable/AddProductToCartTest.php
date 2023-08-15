<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Downloadable;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\GraphQl\Quote\GetCustomOptionsWithUIDForQueryBySku;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test adding downloadable products via AddProductsToCart mutation with various
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
     * @var GetCustomOptionsWithUIDForQueryBySku
     */
    private $getCustomOptionsWithIDV2ForQueryBySku;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomOptionsWithIDV2ForQueryBySku = $this->objectManager->get(
            GetCustomOptionsWithUIDForQueryBySku::class
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->cartId) {
            $this->removeQuote($this->cartId);
        }
        parent::tearDown();
    }

    /**
     * Function returns array of all product's links
     *
     * @param string $productSku
     * @return array
     */
    private function getProductsLinks(string $productSku) : array
    {
        $result = [];
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        $product = $productRepository->get($productSku, false, null, true);

        foreach ($product->getDownloadableLinks() as $linkObject) {
            $result[$linkObject->getLinkId()] = [
                'title' => $linkObject->getTitle(),
                'link_type' => null, //deprecated field
                'price' => $linkObject->getPrice(),
            ];
        }
        return $result;
    }

    /**
     * @param string $productSku
     * @return string
     */
    private function getDownloadableProductInfo(string $productSku)
    {
        $links = $this->getProductsLinks($productSku);
        $linkId = key($links);

        $itemOptions = $this->getCustomOptionsWithIDV2ForQueryBySku->execute($productSku);

        /* Add downloadable product link data to the "selected_options" */
        $itemOptions['selected_options'][] = $this->generateProductLinkSelectedOptions($linkId);

        return preg_replace(
            '/"([^"]+)"\s*:\s*/',
            '$1:',
            json_encode($itemOptions)
        );
    }

    /**
     * Generates UID for downloadable links
     *
     * @param int $linkId
     * @return string
     */
    private function generateProductLinkSelectedOptions(int $linkId): string
    {
        return base64_encode("downloadable/$linkId");
    }

    /**
     * Returns GraphQl query string
     *
     * @param string $cartId
     * @param int $quantity
     * @param string $productSku
     * @param string $customizableOptions
     * @return string
     */
    private function getQuery(
        string $cartId,
        int $quantity,
        string $productSku,
        string $customizableOptions
    ): string {
        return <<<MUTATION
mutation {
    addProductsToCart(
        cartId: "{$cartId}",
        cartItems: [
            {
                sku: "{$productSku}"
                quantity: {$quantity}
                {$customizableOptions}
            }
        ]
    ) {
    user_errors {
    code
    message
    }
        cart {
            items {
                quantity
                ... on DownloadableCartItem {
                    links {
                      id
                      uid
                      title
                      uid
                      sample_url
                    }
                }
            }
        }
    }
}
MUTATION;
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_permissions_for_logged_in.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testDownloadableProductIsAddedToCart()
    {
        // Make the association between downloadable product with allowed category
        /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
        $categoryLinkManagement = Bootstrap::getObjectManager()->create(
            CategoryLinkManagementInterface::class
        );
        $categoryLinkManagement->assignProductToCategories(
            'downloadable-product',
            [3]
        );
        $this->reindexCatalogPermissions();

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $productSku = 'downloadable-product';
        $desiredQuantity = 5;

        $links = $this->getProductsLinks($productSku);
        $linkId = key($links);

        $productOptionsQuery = $this->getDownloadableProductInfo($productSku);

        $query = $this->getQuery(
            $cartId,
            $desiredQuantity,
            $productSku,
            trim($productOptionsQuery, '{}')
        );

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertEmpty($response['addProductsToCart']['user_errors']);

        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertNotEmpty($cartItems[0]['links']);
        $this->assertEquals(
            $linkId,
            $cartItems[0]['links'][0]['id']
        );
        $this->assertEquals(
            'Downloadable Product Link',
            $cartItems[0]['links'][0]['title']
        );
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testDownloadableProductIsDeniedToCartForCustomer()
    {
        // Make the association between downloadable product with denied category
        /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
        $categoryLinkManagement = Bootstrap::getObjectManager()->create(
            CategoryLinkManagementInterface::class
        );
        $categoryLinkManagement->assignProductToCategories(
            'downloadable-product',
            [4]
        );
        $this->reindexCatalogPermissions();

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $productSku = 'downloadable-product';
        $desiredQuantity = 5;

        $productOptionsQuery = $this->getDownloadableProductInfo($productSku);

        $query = $this->getQuery(
            $cartId,
            $desiredQuantity,
            $productSku,
            trim($productOptionsQuery, '{}')
        );

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->assertNotEmpty($response['addProductsToCart']['user_errors']);
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $productSku,
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertEmpty($cartItems);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/category.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_permissions_for_guest.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testDownloadableProductIsDeniedToCartForGuest()
    {
        $this->reindexCatalogPermissions();
        // Make the association between downloadable product with denied category
        /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
        $categoryLinkManagement = Bootstrap::getObjectManager()->create(
            CategoryLinkManagementInterface::class
        );
        $categoryLinkManagement->assignProductToCategories(
            'downloadable-product',
            [4]
        );
        $this->reindexCatalogPermissions();

        $cartId = $this->createEmptyCart();

        $productSku = 'downloadable-product';
        $desiredQuantity = 5;

        $productOptionsQuery = $this->getDownloadableProductInfo($productSku);

        $query = $this->getQuery(
            $cartId,
            $desiredQuantity,
            $productSku,
            trim($productOptionsQuery, '{}')
        );

        $response = $this->graphQlMutation(
            $query,
            [],
            ''
        );

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertNotEmpty($response['addProductsToCart']['user_errors']);
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $productSku,
            $response['addProductsToCart']['user_errors'][0]['message']
        );
        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items_groups 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testDownloadableProductIsDeniedToCartForGuestByConfiguration()
    {
        $this->reindexCatalogPermissions();
        // Make the association between downloadable product with denied category
        /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
        $categoryLinkManagement = Bootstrap::getObjectManager()->create(
            CategoryLinkManagementInterface::class
        );
        $categoryLinkManagement->assignProductToCategories(
            'downloadable-product',
            [4]
        );
        $this->reindexCatalogPermissions();

        $cartId = $this->createEmptyCart();

        $productSku = 'downloadable-product';
        $desiredQuantity = 5;

        $productOptionsQuery = $this->getDownloadableProductInfo($productSku);

        $query = $this->getQuery(
            $cartId,
            $desiredQuantity,
            $productSku,
            trim($productOptionsQuery, '{}')
        );

        $response = $this->graphQlMutation(
            $query,
            [],
            ''
        );

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertNotEmpty($response['addProductsToCart']['user_errors']);
        $this->assertEquals(
            'PERMISSION_DENIED',
            $response['addProductsToCart']['user_errors'][0]['code']
        );
        $this->assertStringContainsString(
            $productSku,
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
        $this->cartId = $response['createEmptyCart'];
        return $this->cartId;
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
}
