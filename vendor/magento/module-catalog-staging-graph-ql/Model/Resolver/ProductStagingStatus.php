<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product;
use Magento\CatalogStagingGraphQl\Model\Products\StagedProductCollector;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Resolve staged status of product for preview queries
 */
class ProductStagingStatus implements ResolverInterface
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var StagedProductCollector
     */
    private $stagedProductCollector;

    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @param VersionManager $versionManager
     * @param StagedProductCollector $stagedProductCollector
     * @param ValueFactory $valueFactory
     * @param DateTime $dateTime
     * @param UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        VersionManager $versionManager,
        StagedProductCollector $stagedProductCollector,
        ValueFactory $valueFactory,
        DateTime $dateTime,
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->versionManager = $versionManager;
        $this->stagedProductCollector = $stagedProductCollector;
        $this->valueFactory = $valueFactory;
        $this->dateTime = $dateTime;
        $this->updateRepository = $updateRepository;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return false;
        }
        /** @var Product $product */
        $product = $value['model'];

        if ($this->isStagedProductVersion($product)) {
            $this->stagedProductCollector->addProductSku($product->getSku());
        }

        return $this->valueFactory->create(function () use ($product) {
            $isStaged = $this->stagedProductCollector->productIsStaged($product);
            return $isStaged;
        });
    }

    /**
     * Check if we are viewing a staged version of a product
     *
     * @param Product $product
     * @return bool
     */
    private function isStagedProductVersion(Product $product): bool
    {
        $currentProductVersion = $product->getData('created_in');
        $currentTime = $this->dateTime->gmtTimestamp();
        if ($currentProductVersion < $currentTime) {
            return false;
        }

        try {
            $productUpdate = $this->updateRepository->get($currentProductVersion);
        } catch (NoSuchEntityException $e) {
            return false;
        }
        if ($productUpdate->getIsRollback()) {
            return false;
        }
        return true;
    }
}
