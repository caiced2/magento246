<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Plugin\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CompositeCollectionProcessor;
use Magento\CatalogPermissionsGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessor\ApplyCategoryPermissionsOnProductProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Postprocess product collection applying catalog permissions on it.
 *
 * This plugin created instead of adding ApplyCategoryPermissionsOnProductProcessor which need to be refactored
 * because depends on processors order. It loading collection to early so next processor fails.
 * Should be fixed in scope of MC-40800
 *
 */
class CompositeCollectionProcessorPlugin
{
    /**
     * @var ApplyCategoryPermissionsOnProductProcessor
     */
    private $applyCategoryPermissionsProcessor;

    /**
     * @param ApplyCategoryPermissionsOnProductProcessor $applyCategoryPermissionsProcessor
     */
    public function __construct(
        ApplyCategoryPermissionsOnProductProcessor $applyCategoryPermissionsProcessor
    ) {
        $this->applyCategoryPermissionsProcessor = $applyCategoryPermissionsProcessor;
    }

    /**
     * Process collection after all preprocessor to avoid loading it too early.
     *
     * @param CompositeCollectionProcessor $subject
     * @param Collection $result
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(
        CompositeCollectionProcessor $subject,
        Collection $result,
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ?ContextInterface $context
    ) {
        $processedCollection = $this->applyCategoryPermissionsProcessor->process(
            $collection,
            $searchCriteria,
            $attributeNames,
            $context
        );

        return $processedCollection;
    }
}
