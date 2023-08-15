<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Rma;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Rma\Api\CommentRepositoryInterface;
use Magento\Rma\Api\Data\CommentInterface;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Model\ResourceModel\Item\CollectionFactory;

/**
 * Load attributes for RMA entity like items, comments, tracks.
 */
class EntityAttributesLoader
{
    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * @var TrackRepositoryInterface
     */
    private $trackRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CommentRepositoryInterface $commentRepository
     * @param TrackRepositoryInterface $trackRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CommentRepositoryInterface $commentRepository,
        TrackRepositoryInterface $trackRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        CollectionFactory $collectionFactory
    ) {
        $this->criteriaBuilder = $criteriaBuilder;
        $this->collectionFactory = $collectionFactory;
        $this->commentRepository = $commentRepository;
        $this->trackRepository = $trackRepository;
    }
    /**
     * Gets RMA items.
     *
     * @param int $rmaEntityId
     * @return ItemInterface[]
     */
    public function getItems(int $rmaEntityId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('rma_entity_id', $rmaEntityId)
            ->setOrder(['order_item_id', 'entity_id'])
            ->addAttributeToSelect('*');
        return $collection->getItems();
    }

    /**
     * Gets RMA comments.
     *
     * @param int $rmaEntityId
     * @return CommentInterface[]
     */
    public function getComments(int $rmaEntityId): array
    {
        $criteria = $this->criteriaBuilder->addFilter('rma_entity_id', $rmaEntityId)
            ->create();
        return $this->commentRepository->getList($criteria)
            ->getItems();
    }

    /**
     * Gets list of track items for RMA.
     *
     * @param int $rmaEntityId
     * @return TrackInterface[]
     */
    public function getTracks(int $rmaEntityId): array
    {
        $searchCriteria = $this->criteriaBuilder->addFilter('rma_entity_id', $rmaEntityId)
            ->create();
        return $this->trackRepository->getList($searchCriteria)
            ->getItems();
    }
}
