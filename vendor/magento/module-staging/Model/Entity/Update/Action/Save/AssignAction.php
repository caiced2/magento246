<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Entity\Update\Action\Save;

use Magento\Staging\Controller\Adminhtml\Entity\Update\Service as UpdateService;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Entity\Update\Action\ActionInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\Entity\Update\CampaignUpdater;

class AssignAction implements ActionInterface
{
    /**
     * @var UpdateService
     */
    protected $updateService;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var EntityStaging
     */
    protected $entityStaging;

    /**
     * @var HydratorInterface
     */
    protected $entityHydrator;

    /**
     * @var CampaignUpdater
     */
    protected $campaignUpdater;

    /**
     * @param UpdateService $updateService
     * @param VersionManager $versionManager
     * @param EntityStaging $entityStaging
     * @param HydratorInterface $entityHydrator
     * @param CampaignUpdater $campaignUpdater
     */
    public function __construct(
        UpdateService $updateService,
        VersionManager $versionManager,
        EntityStaging $entityStaging,
        HydratorInterface $entityHydrator,
        CampaignUpdater $campaignUpdater
    ) {
        $this->updateService = $updateService;
        $this->versionManager = $versionManager;
        $this->entityStaging = $entityStaging;
        $this->entityHydrator = $entityHydrator;
        $this->campaignUpdater = $campaignUpdater;
    }

    /**
     * Assign action
     *
     * @param array $params
     * @return bool
     */
    public function execute(array $params)
    {
        $this->validateParams($params);
        $stagingData = $params['stagingData'];
        $arguments = [];
        if (isset($stagingData['update_id']) && !empty($stagingData['update_id'])) {
            $arguments['copy_origin_in'] = $stagingData['update_id'];
        }

        /** @var Update $update */
        $update = $this->updateService->assignUpdate($stagingData);
        $entity = $this->entityHydrator->hydrate($params['entityData']);
        $this->versionManager->setCurrentVersionId($update->getId());
        if (is_object($entity)) {
            $result = $this->entityStaging->schedule($entity, $this->versionManager->getVersion()->getId(), $arguments);
            if ($result) {
                $this->campaignUpdater->updateCampaignStatus($update);
            }
        }
        //TODO: MAGETWO-51367 while product hydrator refactored
        if ($entity === true) {
            $this->campaignUpdater->updateCampaignStatus($update);
        }
        return true;
    }

    /**
     * Validate input parameters
     *
     * @param array $params
     * @return void
     */
    protected function validateParams(array $params)
    {
        foreach (['stagingData', 'entityData'] as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The required parameter is "%1". Set parameter and try again.', $requiredParam)
                );
            }
            if (!is_array($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The "%1" parameter is invalid. Verify the parameter and try again.', $requiredParam)
                );
            }
        }
    }
}
