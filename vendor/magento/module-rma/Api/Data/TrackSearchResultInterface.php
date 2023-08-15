<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api\Data;

/**
 * Interface TrackSearchResultInterface
 * @api
 * @since 100.0.2
 */
interface TrackSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Rma list
     *
     * @return \Magento\Rma\Api\Data\TrackInterface[]
     */
    public function getItems();

    /**
     * Set Rma list
     *
     * @param \Magento\Rma\Api\Data\TrackInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
