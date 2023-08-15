<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\PeriodSync\Scheduler as PeriodSyncScheduler;

class SynchronizeEntityPeriod
{
    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var PeriodSyncScheduler
     */
    private $scheduler;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PeriodSyncScheduler $scheduler
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PeriodSyncScheduler $scheduler
    ) {
        $this->updateRepository = $updateRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scheduler = $scheduler;
    }

    /**
     * Synchronise Entity
     *
     * @return void
     */
    public function execute()
    {
        $this->filterBuilder->setField('moved_to');
        $this->filterBuilder->setConditionType('notnull');
        $this->searchCriteriaBuilder->addFilters([$this->filterBuilder->create()]);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->updateRepository->getList($searchCriteria);

        $updateIds = [];
        foreach ($searchResults->getItems() as $update) {
            $updateIds[] = $update->getId();
        }
        $this->scheduler->execute($updateIds);
    }
}
