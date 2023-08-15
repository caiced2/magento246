<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Update;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\UpdateRepository;

class CampaignUpdater
{
    /**
     * @var IncludesRetriever
     */
    protected $includesRetriever;

    /**
     * @var UpdateRepository
     */
    protected $updateRepository;

    /**
     * @param IncludesRetriever $includesRetriever
     * @param UpdateRepository $updateRepository
     */
    public function __construct(
        IncludesRetriever $includesRetriever,
        UpdateRepository $updateRepository
    ) {
        $this->includesRetriever = $includesRetriever;
        $this->updateRepository = $updateRepository;
    }

    /**
     * Update update status
     *
     * @param UpdateInterface $update
     * @return void
     */
    public function updateCampaignStatus(UpdateInterface $update)
    {
        $includes = array_column($this->includesRetriever->getIncludes([$update->getId()]), 'includes');
        if (array_sum($includes) >= 2) {
            $update->setIsCampaign(true);
            $this->updateRepository->save($update);
        }
    }
}
