<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api\Data;

/**
 * Interface RmaSearchResultInterface
 * @api
 * @since 100.0.2
 */
interface RmaSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Rma list
     *
     * @return \Magento\Rma\Api\Data\RmaInterface[]
     */
    public function getItems();

    /**
     * Set Rma list
     *
     * @param \Magento\Rma\Api\Data\RmaInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
