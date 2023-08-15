<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ConfigurableProductStaging\Plugin\Catalog;

use Magento\Catalog\Model\Product\Edit\WeightResolver;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\ConfigurableProduct\Model\Product\VariationHandler;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Update Configurations for configurable product
 */
class UpdateConfigurationsStaging
{
    private const KEYS_POST = [
        'status',
        'sku',
        'name',
        'price',
        'configurable_attribute',
        'weight',
        'media_gallery',
        'swatch_image',
        'small_image',
        'thumbnail',
        'image',
    ];

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var VariationHandler
     */
    private $variationHandler;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var ProductStagingInterface
     */
    private $productStaging;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param VariationHandler $variationHandler
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param ProductStagingInterface $productStaging
     * @param Json $json
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        VariationHandler $variationHandler,
        VersionManager $versionManager,
        UpdateRepositoryInterface $updateRepository,
        ProductStagingInterface $productStaging,
        Json $json
    ) {
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->variationHandler = $variationHandler;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->productStaging = $productStaging;
        $this->json = $json;
    }

    /**
     * Update data for configurable product configurations
     *
     * @param InitializationHelper $subject
     * @param Product $configurableProduct
     *
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitialize(
        InitializationHelper $subject,
        Product $configurableProduct
    ) {
        $configurations = $this->getConfigurations();
        $configurations = $this->variationHandler->duplicateImagesForVariations($configurations);

        if (count($configurations) && $configurableProduct->getTypeId() === Configurable::TYPE_CODE) {
            $currentId = (int) $this->versionManager->getCurrentVersion()->getId();
            if ($currentId > 1 && $currentId < VersionManager::MAX_VERSION) {
                $update = $this->updateRepository->get($currentId);
                $update->setIsCampaign(true);
                $this->updateRepository->save($update);

                foreach ($configurations as $productId => $productData) {
                    /** @var Product $product */
                    $product = $this->productRepository->getById(
                        $productId,
                        true,
                        $this->request->getParam('store', 0)
                    );
                    $productData = $this->variationHandler->processMediaGallery($product, $productData);
                    $product->addData($productData);
                    $this->productStaging->schedule($product, $currentId);
                }
            } else {
                foreach ($configurations as $productId => $productData) {
                    /** @var Product $product */
                    $product = $this->productRepository->getById(
                        $productId,
                        true,
                        $this->request->getParam('store', 0)
                    );
                    $productData = $this->variationHandler->processMediaGallery($product, $productData);
                    $product->addData($productData);
                    if ($product->hasDataChanges()) {
                        $product->save();
                    }
                }
            }
        }
        return $configurableProduct;
    }

    /**
     * Get configurations from request
     *
     * @return array
     */
    private function getConfigurations() : array
    {
        $result = [];
        $configurableMatrix = $this->request->getParam('configurable-matrix-serialized', "[]");
        if (isset($configurableMatrix) && $configurableMatrix !== '') {
            $configurableMatrix = $this->json->unserialize($configurableMatrix);

            foreach ($configurableMatrix as $item) {
                if (empty($item['was_changed'])) {
                    continue;
                } else {
                    unset($item['was_changed']);
                }

                if (!$item['newProduct']) {
                    $result[$item['id']] = $this->mapData($item);
                    if (isset($item['qty'])) {
                        $result[$item['id']]['quantity_and_stock_status']['qty'] = $item['qty'];
                    }
                    if (!empty($item['weight']) && $item['weight'] >= 0) {
                        $result[$item['id']]['type_id'] = Type::TYPE_SIMPLE;
                        $result[$item['id']]['product_has_weight'] = WeightResolver::HAS_WEIGHT;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Map data from POST
     *
     * @param array $item
     * @return array
     */
    private function mapData(array $item) : array
    {
        $result = [];
        foreach (self::KEYS_POST as $key) {
            if (isset($item[$key])) {
                $result[$key] = $item[$key];
            }
        }
        return $result;
    }
}
