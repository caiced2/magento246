<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions\Product\Bundle\Option;

use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class VisibilityTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * Given a second store/website/store view
     * And simple product 1 is created and assigned to the main website
     * And simple product 2 is created and assigned to the second website
     * And a bundle product is created and assigned to both websites
     * When a products query searching for the bundle product is sent without any store header
     * Then the bundle product appears with only the simple product 1 appearing as an option among both options
     * When a products query searching for the bundle product is sent with the store header for the second store
     * Then the bundle product appears with only the simple product 2 appearing as an option among both options
     *
     * @param bool $isQueryInSecondStore
     * @dataProvider storeDataProvider
     * @return void
     */
    #[
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'store_group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$store_group2.id$'], 'store2'),
        DataFixture(
            ProductFixture::class,
            [
                'category_ids' => [2], // Default Category
                'website_ids' => [1], // Default Website
            ],
            as: 'simple_product_1'
        ),
        DataFixture(
            ProductFixture::class,
            [
                'category_ids' => [2], // Default Category
                'website_ids' => ['$website2.id$'],
            ],
            as: 'simple_product_2'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'Simple Product 1 Option',
                'required' => false,
                'product_links' => ['$simple_product_1$'],
            ],
            as: 'opt1'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'Simple Product 2 Option',
                'required' => false,
                'product_links' => ['$simple_product_2$'],
            ],
            as: 'opt2'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'category_ids' => [2], // Default Category
                'sku' => 'bundle-product-in-both-websites',
                'website_ids' => [1, '$website2.id$'],
                '_options' => ['$opt1$', '$opt2$'],
            ],
            as: 'bundle_product_in_both_websites'
        ),
    ]
    public function testBundleProductOptionsAreOnlyThoseThatBelongToCurrentStoreContextWithCatalogPermissionsDisabled(
        bool $isQueryInSecondStore
    ) {
        $this->testBundleProductOptionsAreOnlyThoseThatBelongToCurrentStoreContext($isQueryInSecondStore);
    }

    /**
     * Given a second store/website/store view
     * And simple product 1 is created and assigned to the main website
     * And simple product 2 is created and assigned to the second website
     * And a bundle product is created and assigned to both websites
     * And Catalog Permissions are enabled
     * And "Allow Browsing Category" is set to "Yes, for Everyone"
     * And "Display Product Prices" is set to "Yes, for Everyone"
     * And "Allow Adding to Cart" is set to "Yes, for Everyone"
     * When a products query searching for the bundle product is sent without any store header
     * Then the bundle product appears with only the simple product 1 appearing as an option among both options
     * When a products query searching for the bundle product is sent with the store header for the second store
     * Then the bundle product appears with only the simple product 2 appearing as an option among both options
     *
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
     *
     * @param bool $isQueryInSecondStore
     * @dataProvider storeDataProvider
     * @return void
     */
    #[
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'store_group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$store_group2.id$'], 'store2'),
        DataFixture(
            ProductFixture::class,
            [
                'category_ids' => [2], // Default Category
                'website_ids' => [1], // Default Website
            ],
            as: 'simple_product_1'
        ),
        DataFixture(
            ProductFixture::class,
            [
                'category_ids' => [2], // Default Category
                'website_ids' => ['$website2.id$'],
            ],
            as: 'simple_product_2'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'Simple Product 1 Option',
                'required' => false,
                'product_links' => ['$simple_product_1$'],
            ],
            as: 'opt1'
        ),
        DataFixture(
            BundleOptionFixture::class,
            [
                'title' => 'Simple Product 2 Option',
                'required' => false,
                'product_links' => ['$simple_product_2$'],
            ],
            as: 'opt2'
        ),
        DataFixture(
            BundleProductFixture::class,
            [
                'category_ids' => [2], // Default Category
                'sku' => 'bundle-product-in-both-websites',
                'website_ids' => [1, '$website2.id$'],
                '_options' => ['$opt1$', '$opt2$'],
            ],
            as: 'bundle_product_in_both_websites'
        ),
    ]
    public function testBundleProductOptionsAreOnlyThoseThatBelongToCurrentStoreContextWithCatalogPermissionsEnabled(
        bool $isQueryInSecondStore
    ) {
        $this->testBundleProductOptionsAreOnlyThoseThatBelongToCurrentStoreContext($isQueryInSecondStore);
    }

    private function testBundleProductOptionsAreOnlyThoseThatBelongToCurrentStoreContext(
        bool $isQueryInSecondStore
    ) {
        $headers = [];

        if ($isQueryInSecondStore) {
            $headers['Store'] = $this->fixtures->get('store2')->getCode();
        }

        $response = $this->graphQlQuery(
            $this->getBundleProductQuery(),
            [],
            '',
            $headers
        );

        $this->assertEquals(
            1,
            $response['products']['total_count']
        );

        $this->assertCount(
            1,
            $response['products']['items']
        );

        $bundleProductItems = $response['products']['items'][0]['items'];

        // both bundle items should appear; the product within the option belonging to the other store should be null
        $this->assertCount(
            2,
            $bundleProductItems
        );

        $simpleProduct1Option = $bundleProductItems[0];

        $this->assertEquals(
            'Simple Product 1 Option',
            $simpleProduct1Option['title']
        );

        $this->assertCount(
            1,
            $simpleProduct1Option['options']
        );

        $simpleProduct2Option = $bundleProductItems[1];

        $this->assertEquals(
            'Simple Product 2 Option',
            $simpleProduct2Option['title']
        );

        $this->assertCount(
            1,
            $simpleProduct2Option['options']
        );

        if ($isQueryInSecondStore) {
            // assert simple product option 1's product is null,
            // and that option 2's product is present as simple product 2
            $this->assertNull(
                $simpleProduct1Option['options'][0]['product']
            );

            /** @var ProductInterface $simpleProduct2 */
            $simpleProduct2 = $this->fixtures->get('simple_product_2');

            $this->assertEquals(
                $simpleProduct2->getName(),
                $simpleProduct2Option['options'][0]['product']['name']
            );

            $this->assertEquals(
                $simpleProduct2->getSku(),
                $simpleProduct2Option['options'][0]['product']['sku']
            );
        } else {
            // assert simple product option 2's product is null,
            // and that option 1's product is present as simple product 1
            $this->assertNull(
                $simpleProduct2Option['options'][0]['product']
            );

            /** @var ProductInterface $simpleProduct1 */
            $simpleProduct1 = $this->fixtures->get('simple_product_1');

            $this->assertEquals(
                $simpleProduct1->getName(),
                $simpleProduct1Option['options'][0]['product']['name']
            );

            $this->assertEquals(
                $simpleProduct1->getSku(),
                $simpleProduct1Option['options'][0]['product']['sku']
            );
        }
    }

    public function storeDataProvider(): array
    {
        return [
            [
                false,
            ],
            [
                true,
            ],
        ];
    }

    private function getBundleProductQuery(): string
    {
        return <<<QUERY
query {
  products (
    search: "bundle"
  ) {
    total_count
    items {
      uid
      name
      sku
      ... on BundleProduct {
        dynamic_sku
        dynamic_price
        dynamic_weight
        price_view
        ship_bundle_items
        items {
          title
          required
          type
          position
          sku
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
  }
}
QUERY;
    }
}
