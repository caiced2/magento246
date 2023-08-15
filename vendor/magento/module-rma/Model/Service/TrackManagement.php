<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Service;

use Magento\Framework\Exception\StateException;

/**
 * Class TrackManagement
 */
class TrackManagement implements \Magento\Rma\Api\TrackManagementInterface
{
    /**
     * Permission checker
     *
     * @var \Magento\Rma\Model\Rma\PermissionChecker
     */
    protected $permissionChecker;

    /**
     * Label service
     *
     * @var \Magento\Rma\Model\Shipping\LabelService
     */
    protected $labelService;

    /**
     * RMA repository
     *
     * @var \Magento\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * Track repository
     *
     * @var \Magento\Rma\Api\TrackRepositoryInterface
     */
    protected $trackRepository;

    /**
     * Constructor
     *
     * @param \Magento\Rma\Model\Rma\PermissionChecker $permissionChecker
     * @param \Magento\Rma\Model\Shipping\LabelService $labelService
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Magento\Rma\Api\TrackRepositoryInterface $trackRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     */
    public function __construct(
        \Magento\Rma\Model\Rma\PermissionChecker $permissionChecker,
        \Magento\Rma\Model\Shipping\LabelService $labelService,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Rma\Api\TrackRepositoryInterface $trackRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder
    ) {
        $this->permissionChecker = $permissionChecker;
        $this->labelService = $labelService;
        $this->rmaRepository = $rmaRepository;
        $this->trackRepository = $trackRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * Get shipping label int the PDF format
     *
     * @param int $id
     * @return string
     */
    public function getShippingLabelPdf($id)
    {
        $this->permissionChecker->checkRmaForCustomerContext();
        return base64_encode($this->labelService->getShippingLabelByRmaPdf($this->rmaRepository->get($id)));
    }

    /**
     * Get track list
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\TrackSearchResultInterface
     */
    public function getTracks($id)
    {
        $this->criteriaBuilder->addFilters(
            ['eq' => $this->filterBuilder->setField('rma_entity_id')->setValue($id)->create()]
        );

        return $this->trackRepository->getList($this->criteriaBuilder->create());
    }

    /**
     * Add track
     *
     * @param int $id
     * @param \Magento\Rma\Api\Data\TrackInterface $track
     *
     * @throws StateException
     * @return bool
     */
    public function addTrack($id, \Magento\Rma\Api\Data\TrackInterface $track)
    {
        if ($this->permissionChecker->isCustomerContext()) {
            throw new StateException(__('The service is unknown. Verify the service and try again.'));
        }
        $rmaEntity = $this->rmaRepository->get($id);
        $track->setRmaEntityId($rmaEntity->getEntityId());

        return (bool)$this->trackRepository->save($track);
    }

    /**
     * Remove track by id
     *
     * @param int $id
     * @param int $trackId
     * @return bool
     * @throws StateException
     */
    public function removeTrackById($id, $trackId)
    {
        if ($this->permissionChecker->isCustomerContext()) {
            throw new StateException(__('The service is unknown. Verify the service and try again.'));
        }
        $this->criteriaBuilder->addFilters(
            ['eq' => $this->filterBuilder->setField('entity_id')->setValue($trackId)->create()]
        );
        $this->criteriaBuilder->addFilters(
            ['eq' => $this->filterBuilder->setField('rma_entity_id')->setValue($id)->create()]
        );
        $tracks = $this->trackRepository->getList($this->criteriaBuilder->create())->getItems();
        $counter = 0;
        foreach ($tracks as $track) {
            $this->trackRepository->delete($track);
            $counter++;
        }

        return $counter === count($tracks);
    }
}
