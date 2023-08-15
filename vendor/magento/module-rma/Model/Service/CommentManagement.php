<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Service;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Rma\Model\Rma\PermissionChecker;
use Magento\Rma\Api\CommentRepositoryInterface;
use Magento\Rma\Api\RmaRepositoryInterface;

/**
 * Class CommentManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CommentManagement implements \Magento\Rma\Api\CommentManagementInterface
{
    /**
     * @var CommentRepositoryInterface
     */
    protected $commentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var PermissionChecker
     */
    private $permissionChecker;

    /**
     * @var RmaRepositoryInterface
     */
    private $repository;

    /**
     * @param CommentRepositoryInterface $commentRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param RmaRepositoryInterface $repository
     * @param PermissionChecker $permissionChecker
     */
    public function __construct(
        CommentRepositoryInterface $commentRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        RmaRepositoryInterface $repository,
        PermissionChecker $permissionChecker
    ) {
        $this->commentRepository = $commentRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->permissionChecker = $permissionChecker;
        $this->repository = $repository;
    }

    /**
     * Add comment
     *
     * @param \Magento\Rma\Api\Data\CommentInterface $comment
     * @return bool
     * @throws \Exception
     */
    public function addComment(\Magento\Rma\Api\Data\CommentInterface $comment)
    {
        /** @todo Find a way to place this logic somewhere else(not to plugins!) */
        $this->permissionChecker->checkRmaForCustomerContext();

        $message = trim($comment->getComment());
        if (!$message) {
            throw new \Magento\Framework\Exception\InputException(__('Please enter a valid comment.'));
        }

        if ($comment->getIsCustomerNotified()) {
            $comment->sendCustomerCommentEmail();
        }
        $comment->setIsAdmin(true);
        $this->commentRepository->save($comment);
        return true;
    }

    /**
     * Comments list
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\CommentSearchResultInterface
     */
    public function commentsList($id)
    {
        /** @todo Find a way to place this logic somewhere else(not to plugins!) */
        $this->permissionChecker->checkRmaForCustomerContext();

        $rmaModel = $this->repository->get($id);

        $this->criteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField('rma_entity_id')
                    ->setValue($rmaModel->getEntityId())
                    ->create(),
            ]
        );
        if ($this->permissionChecker->isCustomerContext()) {
            $this->criteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('is_visible_on_front')
                        ->setValue(1)
                        ->create(),
                ]
            );
        }

        $criteria = $this->criteriaBuilder->create();
        return $this->commentRepository->getList($criteria);
    }
}
