<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Rma\Api\Data\RmaSearchResultInterface;
use Magento\RmaGraphQl\Model\Formatter\Rma as RmaFormatter;

/**
 * Formatter for RMA list
 */
class Returns
{
    /**
     * @var RmaFormatter
     */
    private $rmaFormatter;

    /**
     * @param Rma $rmaFormatter
     */
    public function __construct(RmaFormatter $rmaFormatter)
    {
        $this->rmaFormatter = $rmaFormatter;
    }

    /**
     * Format RMA search results to GraphQL schema format
     *
     * @param RmaSearchResultInterface $searchResults
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     * @throws RuntimeException
     */
    public function format(RmaSearchResultInterface $searchResults): array
    {
        $pageSize = $searchResults->getSearchCriteria()->getPageSize();

        $returns = [];

        foreach ($searchResults->getItems() as $item) {
            $returns[] = $this->rmaFormatter->format($item);
        }

        return [
            'items' => $returns,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $searchResults->getSearchCriteria()->getCurrentPage(),
                'total_pages' => $pageSize ? ((int)ceil($searchResults->getTotalCount() / $pageSize)) : 0
            ],
            'total_count' => $searchResults->getTotalCount()
        ];
    }
}
