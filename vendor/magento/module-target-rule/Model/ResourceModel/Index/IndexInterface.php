<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\ResourceModel\Index;

use Magento\TargetRule\Model\Index as IndexModel;

/**
 * @api
 * @since 100.0.2
 */
interface IndexInterface
{
    /**
     * Load products by segment ID
     *
     * @param IndexModel $indexModel
     * @param int $segmentId
     * @return int[]
     */
    public function loadProductIdsBySegmentId(IndexModel $indexModel, $segmentId);

    /**
     * Save matched product Ids by customer segments
     *
     * @param IndexModel $indexModel
     * @param int $segmentId
     * @param int[] $productIds
     * @return $this
     */
    public function saveResultForCustomerSegments(IndexModel $indexModel, $segmentId, array $productIds);

    /**
     * Clean index by store
     *
     * @param \Magento\Store\Model\Store|int|array|null $store
     * @return $this
     */
    public function cleanIndex($store = null);

    /**
     * Delete index by product
     *
     * @param int|null $entityId
     * @return $this
     */
    public function deleteProductFromIndex($entityId = null);
}
