<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api\Data;

/**
 * Interface CommentSearchResultInterface
 * @api
 * @since 100.0.2
 */
interface CommentSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Rma Status History list
     *
     * @return \Magento\Rma\Api\Data\CommentInterface[]
     */
    public function getItems();

    /**
     * Set Rma Status History list
     *
     * @param \Magento\Rma\Api\Data\CommentInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
