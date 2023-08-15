<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api;

/**
 * Interface RmaRepositoryInterface
 * @api
 * @since 100.0.2
 */
interface RmaRepositoryInterface
{
    /**
     * Return data object for specified RMA id
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function get($id);

    /**
     * Return list of RMA data objects based on search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Rma\Api\Data\RmaSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save RMA
     *
     * @param \Magento\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return \Magento\Rma\Api\Data\RmaInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Magento\Rma\Api\Data\RmaInterface $rmaDataObject);

    /**
     * Delete RMA
     *
     * @param \Magento\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Magento\Rma\Api\Data\RmaInterface $rmaDataObject);
}
