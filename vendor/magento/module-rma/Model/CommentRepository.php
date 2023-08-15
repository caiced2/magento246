<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Rma\Api\CommentRepositoryInterface;
use Magento\Rma\Api\Data\CommentInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Rma\Api\Data\CommentInterfaceFactory;
use Magento\Rma\Api\Data\CommentSearchResultInterfaceFactory;
use Magento\Rma\Model\Spi\CommentResourceInterface;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @var CommentResourceInterface
     */
    private $commentResource;

    /**
     * @var CommentInterfaceFactory
     */
    private $commentFactory;

    /**
     * @var CommentSearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param CommentResourceInterface $commentResource
     * @param CommentInterfaceFactory $commentFactory
     * @param CommentSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        CommentResourceInterface $commentResource,
        CommentInterfaceFactory $commentFactory,
        CommentSearchResultInterfaceFactory $searchResultFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->commentResource = $commentResource;
        $this->commentFactory = $commentFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $entity = $this->commentFactory->create();
        $this->commentResource->load($entity, $id);
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResult = $this->searchResultFactory->create();
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function save(CommentInterface $comment)
    {
        try {
            $this->commentResource->save($comment);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the RMA comment.'), $e);
        }

        return $comment;
    }

    /**
     * @inheritdoc
     */
    public function delete(CommentInterface $comment)
    {
        try {
            $this->commentResource->delete($comment);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the RMA comment.'), $e);
        }
        return true;
    }
}
