<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Update;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\UpdateRepository;
use Magento\Staging\Model\VersionHistoryInterface;

class Cleaner
{
    /**
     * @var UpdateRepository
     */
    private $updateRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var IncludesRetriever
     */
    private $includes;

    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @param UpdateRepository $updateRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param IncludesRetriever $includes
     * @param FilterBuilder $filterBuilder
     * @param VersionHistoryInterface $versionHistory
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        UpdateRepository $updateRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        IncludesRetriever $includes,
        FilterBuilder $filterBuilder,
        VersionHistoryInterface $versionHistory
    ) {
        $this->updateRepository = $updateRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->includes = $includes;
        $this->versionHistory = $versionHistory;
    }

    /**
     * Remove empty updates
     *
     * @return void
     */
    public function execute()
    {
        $updatesToDelete = $this->getDeleteIdList();

        if (!empty($updatesToDelete)) {
            $this->searchCriteriaBuilder->addFilter('id', $updatesToDelete, 'in');
            $updatesToDelete = $this->getListItemsBySearchCriteria($this->searchCriteriaBuilder->create());
            foreach ($updatesToDelete as $update) {
                $this->updateRepository->delete($update);
            }
        }
    }

    /**
     * Gets list of updates ids which not equal to the current update's version.
     *
     * @return array
     */
    private function getUpdateIdList()
    {
        $this->searchCriteriaBuilder->addFilter('moved_to', null, 'null');
        $this->searchCriteriaBuilder->addFilter('is_rollback', null, 'null');
        $this->searchCriteriaBuilder->addFilter('id', $this->versionHistory->getCurrentId(), 'neq');

        $updateIds = array_keys($this->getListItemsBySearchCriteria($this->searchCriteriaBuilder->create()));
        $rollbackIds = $this->getPastRollbackIdList();
        $updateIds = array_merge($updateIds, $rollbackIds);
        $movedIds = $this->getMovedToIdList();
        return array_diff($updateIds, $movedIds);
    }

    /**
     * Gets list of rollback ids in the past without updates.
     *
     * @return array
     */
    private function getPastRollbackIdList()
    {
        // get list of rollback ids
        $this->searchCriteriaBuilder->addFilter('is_rollback', null, 'notnull');
        $this->searchCriteriaBuilder->addFilter('id', $this->versionHistory->getCurrentId(), 'lt');
        $rollbackIdList = array_keys($this->getListItemsBySearchCriteria($this->searchCriteriaBuilder->create()));

        // get list of rollbacks with updates
        $this->searchCriteriaBuilder->addFilter('rollback_id', $rollbackIdList, 'in');
        $items = $this->getListItemsBySearchCriteria($this->searchCriteriaBuilder->create());
        $rollbackIdListWithUpdates = array_map(function (UpdateInterface $update) {
            return $update->getRollbackId();
        }, $items);

        // filter only rollbacks without updates
        return array_diff($rollbackIdList, $rollbackIdListWithUpdates);
    }

    /**
     * Gets id list of moved to updates.
     *
     * @return array
     */
    private function getMovedToIdList()
    {
        $this->searchCriteriaBuilder->addFilter('moved_to', null, 'notnull');
        $items = $this->getListItemsBySearchCriteria($this->searchCriteriaBuilder->create());
        return array_map(function (UpdateInterface $update) {
            return $update->getMovedTo();
        }, $items);
    }

    /**
     * Gets list of updates ids which should be deleted.
     *
     * @return array
     */
    private function getDeleteIdList()
    {
        $updateIds = $this->getUpdateIdList();
        $includes = $this->includes->getIncludes($updateIds);
        $includeIdList = array_unique(array_column($includes, 'created_in'));
        return array_diff($updateIds, $includeIdList);
    }

    /**
     * Gets items by search criteria.
     *
     * @param SearchCriteria $searchCriteria
     * @return UpdateInterface[]
     */
    private function getListItemsBySearchCriteria(SearchCriteria $searchCriteria)
    {
        return $this->updateRepository->getList($searchCriteria)
            ->getItems();
    }
}
