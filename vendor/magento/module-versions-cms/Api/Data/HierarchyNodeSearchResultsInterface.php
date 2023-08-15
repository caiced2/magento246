<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Api\Data;

/**
 * Interface for hierarchy node search results.
 * @api
 * @since 100.0.2
 */
interface HierarchyNodeSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get nodes list.
     *
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface[]
     */
    public function getItems();

    /**
     * Set nodes list.
     *
     * @param \Magento\VersionsCms\Api\Data\HierarchyNodeInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
