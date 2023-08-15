<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Staging\Api\Data\UpdateSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Model\Entity\PeriodSync\Scheduler as PeriodSyncScheduler;
use Magento\Staging\Model\ResourceModel\Update as UpdateResource;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Update\Validator;

/**
 * Represents UpdateRepository class
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateRepository implements UpdateRepositoryInterface
{
    /**
     * @var SearchResultFactory
     */
    protected $searchResultFactory;

    /**
     * @var UpdateResource
     */
    protected $resource;

    /**
     * @var UpdateFactory
     */
    protected $updateFactory;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var VersionHistoryInterface
     */
    protected $versionHistory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var PeriodSyncScheduler
     */
    private $periodSyncScheduler;

    /**
     * @param SearchResultFactory $searchResultFactory
     * @param UpdateResource $resource
     * @param UpdateFactory $updateFactory
     * @param Validator $validator
     * @param VersionHistoryInterface $versionHistory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param PeriodSyncScheduler $periodSyncScheduler
     */
    public function __construct(
        SearchResultFactory $searchResultFactory,
        UpdateResource $resource,
        UpdateFactory $updateFactory,
        Validator $validator,
        VersionHistoryInterface $versionHistory,
        CollectionProcessorInterface $collectionProcessor,
        PeriodSyncScheduler $periodSyncScheduler
    ) {
        $this->searchResultFactory = $searchResultFactory;
        $this->resource = $resource;
        $this->updateFactory = $updateFactory;
        $this->validator = $validator;
        $this->versionHistory = $versionHistory;
        $this->collectionProcessor = $collectionProcessor;
        $this->periodSyncScheduler = $periodSyncScheduler;
    }

    /**
     * Loads a specified update.
     *
     * @param int $id
     * @return UpdateInterface
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        /** @var Update $update */
        $update = $this->updateFactory->create();
        if ($id == \Magento\Staging\Model\VersionManager::MIN_VERSION) {
            $update->setId($id);
        } else {
            $this->resource->load($update, $id);
            if (!$update->getId()) {
                throw new NoSuchEntityException(
                    __('The update with the "%1" ID doesn\'t exist. Verify the ID and try again.', $id)
                );
            }
            if ($update->getRollbackId()) {
                $update->setEndTime($this->get($update->getRollbackId())->getStartTime());
            }
        }

        return $update;
    }

    /**
     * Lists updates that match specified search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\Staging\Api\Data\UpdateSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResult = $this->searchResultFactory->create();
        $searchResult->setSearchCriteria($criteria);
        $this->collectionProcessor->process($criteria, $searchResult);
        return $searchResult;
    }

    /**
     * Deletes a specified update.
     *
     * @param UpdateInterface $entity
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(UpdateInterface $entity)
    {
        try {
            $entityId = $entity->getId();
            if ($this->versionHistory->getCurrentId() == $entityId) {
                throw new CouldNotDeleteException(__("The active update can't be deleted."));
            }
            $rollbackId = $entity->getRollbackId();
            if ($rollbackId
                && $rollbackId !== $this->getVersionMaxIdByTime(time())
                && !$this->resource->isRollbackAssignedToUpdates($rollbackId, [$entityId])
            ) {
                $this->resource->delete($this->get($rollbackId));
            }
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Performs persist operations for a specified update.
     *
     * @param UpdateInterface $entity
     * @return UpdateInterface
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function save(UpdateInterface $entity)
    {
        try {
            $updateIdsToSync = [];
            if (!$entity->getId()) {
                $this->validator->validateCreate($entity);
                $entity->setId($this->getIdForEntity($entity));
                $entity->isObjectNew(true);
            } else {
                $this->validator->validateUpdate($entity);
                $oldUpdate = $this->updateFactory->create();
                $id = $entity->getId();
                $this->resource->load($oldUpdate, $id);
                $entityStartTime = $entity->getStartTime() ? strtotime($entity->getStartTime()) : 0;
                $oldUpdateStartTime = $oldUpdate->getStartTime() ? strtotime($oldUpdate->getStartTime()) : 0;

                if ($entityStartTime != $oldUpdateStartTime) {
                    if ($id <= $this->versionHistory->getCurrentId()) {
                        throw new ValidatorException(
                            __("The start time can't be changed while the update is active. "
                                . "Please wait until the update is complete and try again.")
                        );
                    }
                    $entity->setOldId($oldUpdate->getId());
                    $entity->setId($this->getIdForEntity($entity));
                    if (!$entity->getIsRollback()) {
                        $updateIdsToSync[] = $oldUpdate->getId();
                    }
                }
            }
            if ($entity->getEndTime()) {
                $entity->setRollbackId($this->getRollback($entity));
            } elseif ($entity->getRollbackId()) {
                $this->delete($this->get($entity->getRollbackId()));
                $entity->setRollbackId(null);
            }
            if ($entity->getId() && isset($oldUpdate) && $entity->getRollbackId() !== $oldUpdate->getRollbackId()) {
                $updateIdsToSync[] = $entity->getId();
            }

            if (!empty($updateIdsToSync)) {
                $this->resource->addCommitCallback(
                    function () use ($updateIdsToSync) {
                        $this->periodSyncScheduler->execute($updateIdsToSync);
                    }
                );
            }

            $this->resource->save($entity);
        } catch (ValidatorException $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('The future update was unable to be saved. Please try again.'));
        }
        return $entity;
    }

    /**
     * Retrieves rollback entity for update
     *
     * @param UpdateInterface $entity
     * @return int
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    protected function getRollback(UpdateInterface $entity)
    {
        if ($entity->getRollbackId()) {
            $rollback = $this->get($entity->getRollbackId());
            $rollback->setStartTime($entity->getEndTime());
        } else {
            $rollback = $this->updateFactory->create();
            $rollback->setName(sprintf('Rollback for "%s"', $entity->getName()));
            $rollback->setStartTime($entity->getEndTime());
            $rollback->setIsRollback(true);
        }
        $rollback->setOldOriginId($entity->getOldId());
        $rollback = $this->save($rollback);
        return $rollback->getId();
    }

    /**
     * Retrieves id for entity
     *
     * @param UpdateInterface $entity
     * @return int
     */
    protected function getIdForEntity(UpdateInterface $entity)
    {
        $timestamp = strtotime($entity->getStartTime());
        try {
            $this->get($timestamp);
            while (true) {
                $this->get(++$timestamp);
            }
        } catch (NoSuchEntityException $e) {
            return $timestamp;
        }
    }

    /**
     * @inheritdoc
     */
    public function getVersionMaxIdByTime($timestamp)
    {
        return $this->resource->getMaxIdByTime($timestamp);
    }
}
