<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Rma;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Model\Rma\Status\HistoryFactory;
use Magento\RmaGraphQl\Model\Validator;

/**
 * RMA comment
 */
class Comment
{
    /**
     * @var HistoryFactory
     */
    private $statusHistoryFactory;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param HistoryFactory $statusHistoryFactory
     * @param Validator $validator
     */
    public function __construct(
        HistoryFactory $statusHistoryFactory,
        Validator $validator
    ) {
        $this->statusHistoryFactory = $statusHistoryFactory;
        $this->validator = $validator;
    }

    /**
     * Add comment to RMA
     *
     * @param RmaInterface $rma
     * @param string $commentText
     * @param bool $isNew
     * @param bool $sendEmail
     * @throws GraphQlInputException
     */
    public function addComment(RmaInterface $rma, string $commentText, bool $isNew = false, $sendEmail = false): void
    {
        if ($isNew) {
            $statusHistory = $this->statusHistoryFactory->create();
            $statusHistory->setRmaEntityId($rma->getEntityId());
            $statusHistory->sendNewRmaEmail();
            $statusHistory->saveSystemComment();
        }

        $commentText = $this->validator->validateString($commentText, 'Please enter a valid message.');

        $comment = $this->statusHistoryFactory->create();
        $comment->setRmaEntityId($rma->getEntityId());

        if ($sendEmail) {
            $comment->setComment($commentText);
            $comment->sendCustomerCommentEmail();
        }

        $comment->saveComment($commentText, true, false);
    }
}
