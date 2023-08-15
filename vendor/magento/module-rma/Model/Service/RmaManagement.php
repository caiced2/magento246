<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Service;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Rma\Api\Data\RmaSearchResultInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\RmaManagementInterface;
use Magento\Rma\Model\Rma\PermissionChecker;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Rma\Model\Rma\Status\History;

/**
 * Class RmaManagement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RmaManagement implements RmaManagementInterface
{
    /**
     * Permission checker
     *
     * @var PermissionChecker
     */
    protected $permissionChecker;

    /**
     * Rma repository
     *
     * @var RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * User context
     *
     * @var UserContextInterface
     *
     * @deprecated 101.0.0 As this property isn't used anymore
     */
    protected $userContext;

    /**
     * Filter builder
     *
     * @var FilterBuilder
     *
     * @deprecated 101.0.0 As this property isn't used anymore
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var SearchCriteriaBuilder
     *
     * @deprecated 101.0.0 As this property isn't used anymore
     */
    protected $criteriaBuilder;

    /** @var History */
    private $statusHistory;

    /**
     * Constructor
     *
     * @param PermissionChecker $permissionChecker
     * @param RmaRepositoryInterface $rmaRepository
     * @param UserContextInterface $userContext
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param History $statusHistory
     */
    public function __construct(
        PermissionChecker $permissionChecker,
        RmaRepositoryInterface $rmaRepository,
        UserContextInterface $userContext,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $criteriaBuilder,
        History $statusHistory
    ) {
        $this->permissionChecker = $permissionChecker;
        $this->rmaRepository = $rmaRepository;
        $this->userContext = $userContext;
        $this->filterBuilder = $filterBuilder;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->statusHistory = $statusHistory;
    }

    /**
     * Save RMA
     *
     * @param \Magento\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function saveRma(\Magento\Rma\Api\Data\RmaInterface $rmaDataObject)
    {
        $isSendAuthEmail = false;
        $this->permissionChecker->checkRmaForCustomerContext();
        foreach ($rmaDataObject->getItems() as $itemModel) {
            if ($rmaDataObject->isStatusNeedsAuthEmail($itemModel->getStatus())
                && $itemModel->hasDataChanges('status')
            ) {
                $isSendAuthEmail = true;
                break;
            }
        }
        $rma = $this->rmaRepository->save($rmaDataObject);
        if ($isSendAuthEmail) {
            $this->statusHistory->setRmaEntityId($rma->getEntityId());
            $this->statusHistory->sendAuthorizeEmail();
        }

        return $rma;
    }

    /**
     * Return list of rma data objects based on search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria search criteria object
     * @return RmaSearchResultInterface rma search result
     */
    public function search(SearchCriteriaInterface $searchCriteria)
    {
        $this->permissionChecker->checkRmaForCustomerContext();

        return $this->rmaRepository->getList($searchCriteria);
    }
}
