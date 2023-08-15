<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\ResourceModel\Product\Price;

use Magento\Catalog\Api\Data\SpecialPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Price\Validation\Result as ValidationResult;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * @magentoAppArea webapi_rest
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/Catalog/_files/category_product.php
 * @magentoDataFixture Magento/Catalog/_files/product_special_price.php
 * @magentoDataFixture Magento/Store/_files/second_store.php
 */
class SpecialPriceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var SpecialPrice
     */
    private $specialPrice;

    /**
     * @var SpecialPriceInterfaceFactory
     */
    private $specialPriceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreRepositoryInterface
     */

    private $storeRepository;
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->specialPrice = $this->objectManager->create(SpecialPrice::class);
        $this->specialPriceFactory = $this->objectManager->create(SpecialPriceInterfaceFactory::class);
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);
    }

    /**
     * @dataProvider getDataProvider
     * @param string[] $skus
     * @param int $count
     * @return void
     */
    public function testGet(array $skus, int $count)
    {
        $pricesData = $this->specialPrice->get($skus);
        $this->assertCount($count, $pricesData);
    }

    /**
     * @return array
     */
    public function testUpdate(): array
    {
        $skus = ['simple', 'simple333'];
        $updateDatetime = new \DateTime();
        $priceFrom = $updateDatetime->modify('+10 days')
            ->format('Y-m-d H:i:s');
        $priceTo = $updateDatetime->modify('+2 days')
            ->format('Y-m-d H:i:s');

        $prices = [];
        foreach ($skus as $sku) {
            $prices[] = $this->specialPriceFactory->create()
                ->setSku($sku)
                ->setStoreId(0)
                ->setPrice(8)
                ->setPriceFrom($priceFrom)
                ->setPriceTo($priceTo);
        }
        $result = $this->specialPrice->update($prices);
        $this->assertTrue($result);
        $pricesData = $this->specialPrice->get($skus);
        $this->assertCount(4, $pricesData);

        return $skus;
    }

    /**
     * Test case when another scheduled update already exist for the same time
     *
     */
    public function testUpdateOverlap()
    {
        $productSku1 = 'simple';
        $productSku2 = 'simple333';
        $storeId1 = $this->storeRepository->get('default')->getId();
        $storeId2 = $this->storeRepository->get('fixture_second_store')->getId();
        $updateDatetime = new \DateTime();
        $priceFrom = $updateDatetime->modify('+10 days')
            ->format('Y-m-d H:i:s');
        $priceTo = $updateDatetime->modify('+2 days')
            ->format('Y-m-d H:i:s');
        $prices[] = $this->specialPriceFactory->create()
            ->setSku($productSku1)
            ->setStoreId($storeId1)
            ->setPrice(8)
            ->setPriceFrom($priceFrom)
            ->setPriceTo($priceTo);
        $pricesOverlap[] = $this->specialPriceFactory->create()
            ->setSku($productSku2)
            ->setStoreId($storeId1)
            ->setPrice(9)
            ->setPriceFrom($priceTo);
        $pricesOverlap[] = $this->specialPriceFactory->create()
            ->setSku($productSku2)
            ->setStoreId($storeId2)
            ->setPrice(9)
            ->setPriceFrom($priceTo);
        $result = $this->specialPrice->update($prices);
        $this->assertTrue($result);
        $pricesData = $this->specialPrice->get([$productSku1]);
        $this->assertCount(4, $pricesData);
        $result = $this->specialPrice->update($pricesOverlap);
        $this->assertTrue($result);
        $pricesData = $this->specialPrice->get([$productSku2]);
        $this->assertCount(2, $pricesData);
    }

    public function testConsequentUpdate(): void
    {
        $sku = 'simple';
        $priceFrom = (new \DateTime())->modify('+3 days');
        $priceTo = (new \DateTime())->modify('+6 days');
        $storeIds = [
            $this->storeRepository->get('default')->getId(),
            $this->storeRepository->get('fixture_second_store')->getId(),
        ];
        $prices = [];
        foreach ($storeIds as $storeId) {
            $prices[] = $this->specialPriceFactory->create()
                ->setSku($sku)
                ->setStoreId($storeId)
                ->setPrice(3)
                ->setPriceFrom($priceFrom->format('Y-m-d H:i:s'))
                ->setPriceTo($priceTo->format('Y-m-d H:i:s'));
        }
        $result = $this->specialPrice->update($prices);
        self::assertTrue($result);

        $updateRepository = $this->objectManager->get(UpdateRepositoryInterface::class);
        $update = $updateRepository->get($priceFrom->getTimestamp());
        self::assertNotEmpty($update->getId());
        $newEndTime = (clone $priceTo)->modify('-2 days');
        $update->setEndTime($newEndTime->format('Y-m-d H:i:s'));
        $updateRepository->save($update);

        $prices = [];
        foreach ($storeIds as $storeId) {
            $prices[] = $this->specialPriceFactory->create()
                ->setSku($sku)
                ->setStoreId($storeId)
                ->setPrice(3)
                ->setPriceFrom($newEndTime->format('Y-m-d H:i:s'))
                ->setPriceTo($priceTo->format('Y-m-d H:i:s'));
        }
        $this->specialPrice->update($prices);
        $validationResult = $this->objectManager->get(ValidationResult::class);
        $this->assertEmpty($validationResult->getFailedItems());
    }

    /**
     * @depends testUpdate
     * @param array $skus
     * @return void
     */
    public function testDelete(array $skus)
    {
        $pricesData = $this->specialPrice->get($skus);

        $prices = [];
        foreach ($pricesData as $priceData) {
            $prices[] = $this->specialPriceFactory->create()
                ->setSku($priceData['sku'])
                ->setStoreId($priceData['store_id'])
                ->setPrice($priceData['value'])
                ->setPriceFrom($priceData['price_from'])
                ->setPriceTo($priceData['price_to']);
        }
        $result = $this->specialPrice->delete($prices);
        $this->assertTrue($result);
        $pricesData = $this->specialPrice->get($skus);
        $this->assertEmpty($pricesData);

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->get('simple');
        $this->assertEmpty($product->getSpecialPrice());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                ['simple'],
                1,
            ],
            [
                ['simple333'],
                0,
            ],
            [
                ['simple', 'simple333'],
                1,
            ],
        ];
    }
}
