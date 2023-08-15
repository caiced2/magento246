<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\ResourceModel\Product\Price;

use Magento\Catalog\Api\Data\SpecialPriceInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Price\Validation\Result as ValidationResult;
use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\CatalogStaging\Model\Product\UpdateScheduler;
use Magento\CatalogStaging\Model\ResourceModel\ProductUpdateRetriever;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Special price persistence.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SpecialPrice implements \Magento\Catalog\Api\SpecialPriceInterface
{
    /**
     * @var Attribute
     */
    private $attributeResource;

    /**
     * @var ValidationResult
     */
    private $validationResult;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductStagingInterface
     */
    private $productStaging;

    /**
     * @var UpdateInterfaceFactory
     */
    private $updateFactory;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var ProductUpdateRetriever
     */
    private $productUpdateRetriever;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    private $specialPriceAttribute;

    /**
     * @var string
     */
    private $productLinkField;

    /**
     * @var int
     */
    private $itemsPerOperation = 500;

    /**
     * @param Attribute $attributeResource
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param MetadataPool $metadataPool
     * @param ValidationResult $validationResult
     * @param UpdateScheduler $updateScheduler
     * @param ProductRepositoryInterface|null $productRepository
     * @param ProductStagingInterface|null $productStaging
     * @param UpdateInterfaceFactory|null $updateFactory
     * @param UpdateRepositoryInterface|null $updateRepository
     * @param VersionManager|null $versionManager
     * @param ProductUpdateRetriever|null $productUpdateRetriever
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Attribute $attributeResource,
        ProductAttributeRepositoryInterface $attributeRepository,
        MetadataPool $metadataPool,
        ValidationResult $validationResult,
        UpdateScheduler $updateScheduler,
        ProductRepositoryInterface $productRepository = null,
        ProductStagingInterface $productStaging = null,
        UpdateInterfaceFactory $updateFactory = null,
        UpdateRepositoryInterface $updateRepository = null,
        VersionManager $versionManager = null,
        ProductUpdateRetriever $productUpdateRetriever = null
    ) {
        $this->attributeResource = $attributeResource;
        $this->validationResult = $validationResult;

        $this->productRepository = $productRepository
            ?? ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $this->productStaging = $productStaging
            ?? ObjectManager::getInstance()->get(ProductStagingInterface::class);
        $this->updateFactory = $updateFactory
            ?? ObjectManager::getInstance()->get(UpdateInterfaceFactory::class);
        $this->updateRepository = $updateRepository
            ?? ObjectManager::getInstance()->get(UpdateRepositoryInterface::class);
        $this->versionManager = $versionManager
            ?? ObjectManager::getInstance()->get(VersionManager::class);
        $this->productUpdateRetriever = $productUpdateRetriever
            ?? ObjectManager::getInstance()->get(ProductUpdateRetriever::class);

        $this->specialPriceAttribute = $attributeRepository->get('special_price');
        $this->productLinkField = $metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();
    }

    /**
     * @inheritdoc
     */
    public function get(array $skus)
    {
        $products = $this->getProductsWithDisabledPreview($skus);
        $populatedItems = $this->getSpecialPrices($products);

        return $populatedItems;
    }

    /**
     * @inheritdoc
     */
    public function update(array $prices)
    {
        foreach ($this->validationResult->getFailedRowIds() as $failedRowId) {
            unset($prices[$failedRowId]);
        }

        $skus = $this->retrievePricesSku($prices);
        $existingPrices = $this->get($skus);

        $connection = $this->attributeResource->getConnection();
        $connection->beginTransaction();
        try {
            $formattedPrices = [];
            /** @var SpecialPriceInterface $price */
            foreach ($prices as $key => $price) {
                try {
                    $productId = $this->getAssociatedProductId($price, $existingPrices);
                } catch (\Exception $e) {
                    $this->validationResult->addFailedItem(
                        $key,
                        $e->getMessage(),
                        [
                            'price' => $price->getPrice(),
                            'sku' => $price->getSku(),
                            'store_id' => $price->getStoreId(),
                            'price_from' => $price->getPriceFrom(),
                            'price_to' => $price->getPriceTo(),
                        ]
                    );
                    continue;
                }

                $formattedPrices[] = [
                    'attribute_id' => $this->specialPriceAttribute->getAttributeId(),
                    'store_id' => $price->getStoreId(),
                    $this->productLinkField => $productId,
                    'value' => $price->getPrice(),
                ];
            }

            foreach (array_chunk($formattedPrices, $this->itemsPerOperation) as $bunch) {
                $connection->insertOnDuplicate($this->specialPriceAttribute->getBackendTable(), $bunch, ['value']);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not save Prices.'),
                $e
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(array $prices)
    {
        $skus = $this->retrievePricesSku($prices);
        $products = $this->getProductsWithDisabledPreview($skus);
        $existingPrices = $this->getSpecialPrices($products);
        $idsToDelete = [];
        $storeProductIds = [];
        foreach ($prices as $key => $price) {
            if (!$price->getPriceFrom()) {
                $this->validationResult->addFailedItem(
                    $key,
                    __(
                        'Invalid attribute %fieldName = %fieldValue.',
                        ['fieldName' => '%fieldName', 'fieldValue' => '%fieldValue']
                    ),
                    ['fieldName' => 'Price From', 'fieldValue' => $price->getPriceFrom()]
                );
                continue;
            }

            $existingPrice = $this->findEqualPrice($price, $existingPrices);
            if ($existingPrice && $price->getPrice() == $existingPrice['value']) {
                $storeProductIds[$price->getStoreId()][] = $existingPrice[$this->productLinkField];
                $idsToDelete[] = $existingPrice[$this->productLinkField];
                continue;
            }

            $this->validationResult->addFailedItem(
                $key,
                __('The requested price is not found.'),
                [
                    'price' => $price->getPrice(),
                    'sku' => $price->getSku(),
                    'store_id' => $price->getStoreId(),
                    'price_from' => $price->getPriceFrom(),
                    'price_to' => $price->getPriceTo(),
                ]
            );
        }

        $connection = $this->attributeResource->getConnection();
        $connection->beginTransaction();
        try {
            foreach ($storeProductIds as $storeId => $ids) {
                foreach (array_chunk($ids, $this->itemsPerOperation) as $bunch) {
                    $connection->delete(
                        $this->specialPriceAttribute->getBackendTable(),
                        [
                            'attribute_id = ?' => $this->specialPriceAttribute->getAttributeId(),
                            $this->productLinkField . ' IN (?)' => $bunch,
                            'store_id = ?' => $storeId
                        ]
                    );
                }

            }

            $this->unscheduleProductsUpdate(array_filter($products, function (array $product) use ($idsToDelete) {
                return in_array($product[$this->productLinkField], $idsToDelete);
            }));
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __('Could not delete Prices'),
                $e
            );
        }

        return true;
    }

    /**
     * Get link field.
     *
     * @return string
     */
    public function getEntityLinkField()
    {
        return $this->productLinkField;
    }

    /**
     * Get special prices data.
     *
     * @param array $products
     * @return array
     */
    private function getSpecialPrices(array $products): array
    {
        $products = array_column($products, null, $this->productLinkField);
        $select = $this->attributeResource->getConnection()
            ->select()
            ->from($this->specialPriceAttribute->getBackendTable())
            ->where($this->productLinkField . ' IN (?)', array_keys($products))
            ->where('attribute_id = ?', $this->specialPriceAttribute->getAttributeId());
        $items = $this->attributeResource->getConnection()->fetchAll($select);

        $populatedItems = [];
        foreach ($items as $item) {
            $entityLinkValue = $item[$this->productLinkField];
            $product = $products[$entityLinkValue];
            $populatedItems[] = [
                $this->productLinkField => (int) $entityLinkValue,
                'value' => (float) $item['value'],
                'store_id' => (int) $item['store_id'],
                'sku' => $product['sku'],
                'price_from' => date('Y-m-d H:i:s', $product['created_in']),
                'price_to' => date('Y-m-d H:i:s', $product['updated_in']),
            ];
        }

        return $populatedItems;
    }

    /**
     * Return exists update otherwise create new.
     *
     * @param SpecialPriceInterface $price
     * @return UpdateInterface
     */
    private function retrieveUpdate(SpecialPriceInterface $price): UpdateInterface
    {
        $updateId = $this->productUpdateRetriever->retrieveUpdateId(
            $price->getSku(),
            $price->getPriceFrom(),
            $price->getPriceTo()
        );
        if ($updateId) {
            $update = $this->updateRepository->get($updateId);
        } else {
            $name = __('Update %1 from %2 to %3.', $price->getSku(), $price->getPriceFrom(), $price->getPriceTo());
            $update = $this->updateFactory->create();
            $update->setName($name);
            $update->setStartTime($price->getPriceFrom());
            $update->setEndTime($price->getPriceTo());
            $this->updateRepository->save($update);
        }

        $priceTo = $update->getRollbackId() ?: VersionManager::MAX_VERSION;
        $price->setPriceFrom(date('Y-m-d H:i:s', $update->getId()));
        $price->setPriceTo(date('Y-m-d H:i:s', $priceTo));

        return $update;
    }

    /**
     * Create new product updates.
     *
     * @param SpecialPriceInterface $price
     * @return void
     */
    private function createProductUpdate(SpecialPriceInterface $price): void
    {
        $update = $this->retrieveUpdate($price);
        $product = $this->productRepository->get($price->getSku(), true, $price->getStoreId());

        $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->versionManager->setCurrentVersionId($update->getId());
        try {
            $this->productStaging->schedule($product, $update->getId());
        } catch (\Exception $e) {
            $this->updateRepository->delete($update);
            throw $e;
        } finally {
            $this->versionManager->setCurrentVersionId($currentVersionId);
        }
    }

    /**
     * Get product id for special price.
     *
     * @param SpecialPriceInterface $price
     * @param array $existingPrices
     * @return int
     */
    private function getAssociatedProductId(SpecialPriceInterface $price, array $existingPrices): int
    {
        $existingPrice = $this->findEqualPrice($price, $existingPrices);
        if ($existingPrice) {
            return (int) $existingPrice[$this->productLinkField];
        }

        $this->createProductUpdate($price);
        $productPreviews = $this->getProductsWithDisabledPreview([$price->getSku()]);
        $productId = null;
        $priceTo = $price->getPriceTo() ? strtotime($price->getPriceTo()) : VersionManager::MAX_VERSION;
        foreach ($productPreviews as $productPreview) {
            if (strtotime($price->getPriceFrom()) == $productPreview['created_in']
                && $priceTo == $productPreview['updated_in']
            ) {
                $productId = $productPreview[$this->productLinkField];
                break;
            }
        }

        if (!$productId) {
            throw new LocalizedException(__('Problem with creating product update.'));
        }

        return $productId;
    }

    /**
     * Unschedule scheduled products.
     *
     * @param array $products
     */
    private function unscheduleProductsUpdate(array $products): void
    {
        $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        foreach ($products as $productData) {
            $productVersion = $productData['created_in'];
            if ($productVersion <= $currentVersionId) {
                continue;
            }

            $this->versionManager->setCurrentVersionId($productVersion);
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productRepository->get($productData['sku'], false, null, true);
            //for ensure that correct product update will be unscheduled
            $product->setData($this->productLinkField, $productData[$this->productLinkField]);
            $this->productStaging->unschedule($product, $productVersion);
        }
        $this->versionManager->setCurrentVersionId($currentVersionId);
    }

    /**
     * Get products with disabled staging preview.
     *
     * @param string[] $skus
     * @return array
     */
    public function getProductsWithDisabledPreview(array $skus)
    {
        return $this->attributeResource->getConnection()->fetchAll(
            $this->attributeResource->getConnection()
                ->select()
                ->from(
                    $this->attributeResource->getTable('catalog_product_entity'),
                    [$this->productLinkField, 'sku', 'created_in', 'updated_in', 'entity_id']
                )
                ->where('sku IN (?)', $skus)
                ->where('created_in')
                ->setPart('disable_staging_preview', true)
        );
    }

    /**
     * Retrieve sku from prices.
     *
     * @param SpecialPriceInterface[] $prices
     * @return array
     */
    private function retrievePricesSku(array $prices): array
    {
        return array_unique(
            array_map(function (SpecialPriceInterface $price) {
                return $price->getSku();
            }, $prices)
        );
    }

    /**
     * Find equal price from list of existing prices.
     *
     * @param SpecialPriceInterface $price
     * @param array $existingPrices
     * @return array|null
     */
    private function findEqualPrice(SpecialPriceInterface $price, array $existingPrices): ?array
    {
        foreach ($existingPrices as $existingPrice) {
            if ($this->priceSelectionsAreEqual($price, $existingPrice)) {
                return $existingPrice;
            }
        }

        return null;
    }

    /**
     * Check that prices are equal.
     *
     * @param SpecialPriceInterface $price
     * @param array $existingPrice
     * @return bool
     */
    private function priceSelectionsAreEqual(SpecialPriceInterface $price, array $existingPrice): bool
    {
        $priceTo = $price->getPriceTo() ? strtotime($price->getPriceTo()) : VersionManager::MAX_VERSION;
        return $price->getSku() === $existingPrice['sku']
            && $price->getStoreId() == $existingPrice['store_id']
            && strtotime($price->getPriceFrom()) === strtotime($existingPrice['price_from'])
            && $priceTo === strtotime($existingPrice['price_to']);
    }
}
