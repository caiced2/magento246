<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api;

/**
 * Interface RmaManagementInterface
 * @api
 * @since 100.0.2
 */
interface RmaManagementInterface
{
    /**
     * Save RMA
     *
     * @param \Magento\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function saveRma(\Magento\Rma\Api\Data\RmaInterface $rmaDataObject);

    /**
     * Return list of rma data objects based on search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Rma\Api\Data\RmaSearchResultInterface
     */
    public function search(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
