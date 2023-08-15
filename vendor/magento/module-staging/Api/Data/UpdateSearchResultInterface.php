<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Api\Data;

/**
 * Update search result interface.
 * @api
 * @since 100.1.0
 */
interface UpdateSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Gets collection items
     *
     * @return \Magento\Staging\Api\Data\UpdateInterface[]
     * @since 100.1.0
     */
    public function getItems();

    /**
     * Set collection items
     *
     * @param \Magento\Staging\Api\Data\UpdateInterface[] $items
     * @return $this
     * @since 100.1.0
     */
    public function setItems(array $items);
}
