<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteStaging\Model;

use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Framework\App\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\CatalogStaging\Model\Product\Retriever as ProductRetriever;
use Magento\Staging\Model\StagingApplier\PostProcessorInterface;
use Magento\Staging\Model\Entity\RetrieverPool;

/**
 * Recollects quotes for product which prices were updated during staging update
 */
class UpdateStagingQuoteItems implements PostProcessorInterface
{
    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @var Config
     */
    private $scopeConfigCache;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var RetrieverPool
     */
    private $retrieverPool;

    /**
     * @param VersionHistoryInterface $versionHistory
     * @param Config $scopeConfigCache
     * @param ProductRepositoryInterface $productRepository
     * @param Quote $quote
     */
    public function __construct(
        VersionHistoryInterface $versionHistory,
        Config $scopeConfigCache,
        ProductRepositoryInterface $productRepository,
        Quote $quote,
        RetrieverPool $retrieverPool
    ) {
        $this->versionHistory = $versionHistory;
        $this->scopeConfigCache = $scopeConfigCache;
        $this->productRepository = $productRepository;
        $this->quote = $quote;
        $this->retrieverPool = $retrieverPool;
    }

    /**
     * Recollects quotes for products which prices were updated during staging update
     *
     * @param int $oldVersionId
     * @param int $currentVersionId
     * @param array $entityIds
     * @param string $entityType
     */
    public function execute(
        int $oldVersionId,
        int $currentVersionId,
        array $entityIds,
        string $entityType
    ): void {
        if ($this->retrieverPool->getRetriever($entityType) instanceof ProductRetriever) {
            foreach ($entityIds as $entityId) {
                $newProduct = $this->productRepository->getById($entityId, false, null, true);
                $this->versionHistory->setCurrentId($oldVersionId);
                $this->scopeConfigCache->clean();
                $product = $this->productRepository->getById($entityId, false, null, true);
                $this->versionHistory->setCurrentId($currentVersionId);
                $this->scopeConfigCache->clean();
                if ($product->getPrice() !== $newProduct->getPrice()) {
                    $this->quote->markQuotesRecollect($entityId);
                }
            }
        }
    }

}
