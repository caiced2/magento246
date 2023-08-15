<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Rma\Api\Data\TrackInterfaceFactory;
use Magento\Rma\Api\Data\TrackSearchResultInterfaceFactory;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Rma\Model\Spi\TrackResourceInterface;

class TrackRepository implements TrackRepositoryInterface
{
    /**
     * @var TrackResourceInterface
     */
    private $trackResource;

    /**
     * @var TrackInterfaceFactory
     */
    private $trackFactory;

    /**
     * @var TrackSearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param TrackResourceInterface $trackResource
     * @param TrackInterfaceFactory $trackFactory
     * @param TrackSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        TrackResourceInterface $trackResource,
        TrackInterfaceFactory $trackFactory,
        TrackSearchResultInterfaceFactory $searchResultFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->trackResource = $trackResource;
        $this->trackFactory = $trackFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $entity = $this->trackFactory->create();
        $this->trackResource->load($entity, $id);
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResult = $this->searchResultFactory->create();
        $this->collectionProcessor->process($criteria, $searchResult);
        $searchResult->setSearchCriteria($criteria);
        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function save(TrackInterface $track)
    {
        try {
            $this->trackResource->save($track);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the RMA tracking.'), $e);
        }
        return $track;
    }

    /**
     * @inheritdoc
     */
    public function delete(TrackInterface $track)
    {
        try {
            $this->trackResource->delete($track);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the RMA tracking.'), $e);
        }
        return true;
    }
}
