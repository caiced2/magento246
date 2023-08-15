<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRuleGraphQl\Model\Resolver\Batch;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogGraphQl\Model\Resolver\Product\ProductFieldsSelector;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product as ProductDataProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\TargetRuleGraphQl\Model\ProductList\TargetRuleProductList;

/**
 * Add target rule products into linked product list.
 */
class TargetRuleProducts
{
    /**
     * @var ProductFieldsSelector
     */
    private $productFieldsSelector;

    /**
     * @var ProductDataProvider
     */
    private $productDataProvider;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TargetRuleProductList
     */
    private $targetRuleProductList;

    /**
     * @param ProductFieldsSelector $productFieldsSelector
     * @param ProductDataProvider $productDataProvider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TargetRuleProductList $targetRuleProductList
     */
    public function __construct(
        ProductFieldsSelector $productFieldsSelector,
        ProductDataProvider $productDataProvider,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TargetRuleProductList $targetRuleProductList
    ) {
        $this->productFieldsSelector = $productFieldsSelector;
        $this->productDataProvider = $productDataProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->targetRuleProductList = $targetRuleProductList;
    }

    /**
     * Add target rule responses.
     *
     * @param ContextInterface $context
     * @param BatchRequestItemInterface[] $requests
     * @param BatchResponse $resultResponse
     * @param string $node
     * @param int $linkType
     * @return BatchResponse
     * @throws LocalizedException
     */
    public function applyTargetRuleResponses(
        ContextInterface $context,
        array $requests,
        BatchResponse $resultResponse,
        string $node,
        int $linkType
    ): BatchResponse {
        $products = [];
        $fields = [];

        foreach ($requests as $request) {
            if (empty($request->getValue()['model'])) {
                throw new LocalizedException(__('"model" value should be specified'));
            }
            $products[] = $request->getValue()['model'];
            $fields[] = $this->productFieldsSelector->getProductFieldsFromInfo($request->getInfo(), $node);
        }
        $fields = array_unique(array_merge(...$fields));

        $related = $this->findTargetRuleRelations($context, $products, $fields, $linkType);

        foreach ($requests as $request) {
            /** @var ProductInterface $product */
            $product = $request->getValue()['model'];
            $result = [];
            if (array_key_exists($product->getId(), $related)) {
                $result = array_map(
                    function ($relatedProduct) {
                        $data = $relatedProduct->getData();
                        $data['model'] = $relatedProduct;
                        return $data;
                    },
                    $related[$product->getId()]
                );
            }
            if (!empty($result)) {
                $response = $resultResponse->findResponseFor($request);
                if (is_array($response) && !empty($response)) {
                    foreach ($response as $responseItem) {
                        $result[] = $responseItem;
                    }
                }
                $resultResponse->addResponse($request, $result);
            }
        }

        return $resultResponse;
    }

    /**
     * Find related products by target rule configuration.
     *
     * @param ContextInterface $context
     * @param ProductInterface[] $products
     * @param string[] $loadAttributes
     * @param int $linkType
     * @return array
     */
    private function findTargetRuleRelations(
        ContextInterface $context,
        array $products,
        array $loadAttributes,
        int $linkType
    ): array {
        $relations = $this->getRelations($context, $products, $linkType);

        if (!$relations) {
            return [];
        }
        $relatedIds = array_map(
            function ($relatedProducts) {
                return array_keys($relatedProducts);
            },
            array_values($relations)
        );
        $relatedIds = array_unique(array_merge(...$relatedIds));
        //Loading products data with attributes.
        $this->searchCriteriaBuilder->addFilter('entity_id', $relatedIds, 'in');
        $relatedSearchResult = $this->productDataProvider->getList(
            $this->searchCriteriaBuilder->create(),
            $loadAttributes,
            false,
            true,
            $context
        );
        //Filling related products map.
        /** @var ProductInterface[] $relatedProducts */
        $relatedProducts = [];
        /** @var ProductInterface $item */
        foreach ($relatedSearchResult->getItems() as $item) {
            $relatedProducts[$item->getId()] = $item;
        }

        //Matching products with related products.
        $relationsData = [];
        foreach ($relations as $productId => $relatedItems) {
            $relationsData[$productId] = array_map(
                function ($id) use ($relatedProducts) {
                    return $relatedProducts[$id];
                },
                array_keys($relatedItems)
            );
        }

        return $relationsData;
    }

    /**
     * Retrieve product relations.
     *
     * @param ContextInterface $context
     * @param ProductInterface[] $products
     * @param int $linkType
     * @return ProductInterface[]
     * @throws LocalizedException
     */
    private function getRelations($context, $products, $linkType): array
    {
        $relations = [];
        foreach ($products as $product) {
            $relations[$product->getId()] =
                $this->targetRuleProductList->getTargetRuleProducts($context, $product, $linkType);
        }
        return $relations;
    }
}
