<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Plugin for CollectionProcessorInterface
 */
class CollectionProcessorInterfacePlugin
{
    private $additionalFields = [
        'allow_open_amount'
    ];

    /**
     * Before process plugin
     *
     * @param CollectionProcessorInterface $subject
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return array
     */
    public function beforeProcess(
        CollectionProcessorInterface $subject,
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ContextInterface $context = null
    ) {
        foreach ($this->additionalFields as $additionalField) {
            if (!in_array($additionalField, $attributeNames, true)) {
                $attributeNames[] = $additionalField;
            }
        }

        return [$collection, $searchCriteria, $attributeNames, $context];
    }
}
