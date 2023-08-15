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
interface CommentManagementInterface
{
    /**
     * Add comment
     *
     * @param \Magento\Rma\Api\Data\CommentInterface $data
     * @return bool
     * @throws \Exception
     */
    public function addComment(\Magento\Rma\Api\Data\CommentInterface $data);

    /**
     * Comments list
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\CommentSearchResultInterface
     */
    public function commentsList($id);
}
