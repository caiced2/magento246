<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\CommentInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Rma comment formatter
 */
class Comment
{
    public const ADMIN_NICKNAME = 'Customer Service';

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param Uid $idEncoder
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Uid $idEncoder,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->idEncoder = $idEncoder;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Format RMA comment according to the GraphQL schema
     *
     * @param CommentInterface $comment
     * @param OrderInterface $order
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function format(CommentInterface $comment, OrderInterface $order): array
    {
        return [
            'uid' => $this->idEncoder->encode((string)$comment->getEntityId()),
            'created_at' => $comment->getCreatedAt(),
            'author_name' => $this->getCommentAuthor($comment, $order),
            'text' => $comment->getComment(),
        ];
    }

    /**
     * Get author of the RMA comment
     *
     * @param CommentInterface $comment
     * @param OrderInterface $order
     * @return string
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function getCommentAuthor(CommentInterface $comment, OrderInterface $order): string
    {
        if ($comment->isAdmin()) {
            return self::ADMIN_NICKNAME;
        }
        $author = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        if (trim($author) === '') {
            try {
                $customer = $this->customerRepository->get($order->getCustomerEmail());
            } catch (NoSuchEntityException $e) {
                throw new GraphQlNoSuchEntityException(__($e->getMessage()));
            }
            $author = $customer->getFirstname() . ' ' . $customer->getLastname();
        }
        return $author;
    }
}
