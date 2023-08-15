<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api;

/**
 * Interface CommentRepositoryInterface
 * @api
 * @since 100.0.2
 */
interface CommentRepositoryInterface
{
    /**
     * Get comment by id
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function get($id);

    /**
     * Get comments list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Rma\Api\Data\CommentSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save comment
     *
     * @param \Magento\Rma\Api\Data\CommentInterface $comment
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Magento\Rma\Api\Data\CommentInterface $comment);

    /**
     * Delete comment
     *
     * @param \Magento\Rma\Api\Data\CommentInterface $comment
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Magento\Rma\Api\Data\CommentInterface $comment);
}
