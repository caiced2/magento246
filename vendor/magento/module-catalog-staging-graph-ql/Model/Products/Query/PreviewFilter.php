<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Products\Query;

use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Filter;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Search;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Retrieve product data based on filters, with support for staging preview
 */
class PreviewFilter implements ProductQueryInterface
{
    /** @var VersionManager */
    private $versionManager;

    /** @var Search */
    private $searchQuery;

    /** @var Filter */
    private $filterQuery;

    /**
     * @param VersionManager $versionManager
     * @param Search $searchQuery
     * @param Filter $filterQuery
     */
    public function __construct(
        VersionManager $versionManager,
        Search $searchQuery,
        Filter $filterQuery
    ) {
        $this->versionManager = $versionManager;
        $this->searchQuery = $searchQuery;
        $this->filterQuery = $filterQuery;
    }

    /**
     * Retrieve catalog product data based on filter input
     *
     * @param array $args
     * @param ResolveInfo $info
     * @param ContextInterface $context
     * @return SearchResult
     * @throws GraphQlInputException
     */
    public function getResult(array $args, ResolveInfo $info, ContextInterface $context): SearchResult
    {
        if ($this->versionManager->isPreviewVersion()) {
            if (isset($args['search'])) {
                throw new GraphQlInputException(__('Search is not supported in preview mode.'));
            }
            $searchResult = $this->filterQuery->getResult($args, $info, $context);
        } else {
            $searchResult = $this->searchQuery->getResult($args, $info, $context);
        }

        return $searchResult;
    }
}
