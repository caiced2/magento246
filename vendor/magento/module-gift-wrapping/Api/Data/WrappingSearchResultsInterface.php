<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Api\Data;

/**
 * Interface WrappingSearchResultsInterface
 * @api
 * @since 100.0.2
 */
interface WrappingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get items
     *
     * @return \Magento\GiftWrapping\Api\Data\WrappingInterface[]
     */
    public function getItems();

    /**
     * Set items
     *
     * @param \Magento\GiftWrapping\Api\Data\WrappingInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
