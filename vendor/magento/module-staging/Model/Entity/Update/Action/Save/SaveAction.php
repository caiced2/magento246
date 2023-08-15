<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Update\Action\Save;

use Magento\Framework\Exception\LocalizedException;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service as UpdateService;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Entity\Update\Action\ActionInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\Update\UpdateValidator;

/**
 * Class SaveAction creates new Update or edits existing Update by data from request
 *
 * Update is scheduled change of a Magento store entities.
 *
 * @see https://devdocs.magento.com/guides/v2.4/mrg/ee/Staging.html
 */
class SaveAction implements ActionInterface
{
    /**
     * @var UpdateService
     * @deprecated 100.1.0 Since functionality if this service was implemented in SaveAction class
     */
    private $updateService;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var HydratorInterface
     */
    private $entityHydrator;

    /**
     * @var EntityStaging
     */
    private $entityStaging;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var UpdateFactory
     */
    private $updateFactory;

    /**
     * @var UpdateValidator
     */
    private $validator;

    /**
     * @param UpdateService $updateService
     * @param VersionManager $versionManager
     * @param HydratorInterface $entityHydrator
     * @param EntityStaging $entityStaging
     * @param UpdateRepositoryInterface|null $updateRepository
     * @param MetadataPool|null $metadataPool
     * @param UpdateFactory|null $updateFactory
     * @param UpdateValidator|null $validator
     */
    public function __construct(
        UpdateService $updateService,
        VersionManager $versionManager,
        HydratorInterface $entityHydrator,
        EntityStaging $entityStaging,
        UpdateRepositoryInterface $updateRepository = null,
        MetadataPool $metadataPool = null,
        UpdateFactory $updateFactory = null,
        UpdateValidator $validator = null
    ) {
        $this->updateService = $updateService;
        $this->versionManager = $versionManager;
        $this->entityHydrator = $entityHydrator;
        $this->entityStaging = $entityStaging;
        $this->updateRepository = $updateRepository ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(UpdateRepositoryInterface::class);
        $this->metadataPool = $metadataPool ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(MetadataPool::class);
        $this->updateFactory = $updateFactory ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(UpdateFactory::class);
        $this->validator = $validator ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(UpdateValidator::class);
    }

    /**
     * @inheritdoc
     *
     * @param array $params
     *
     * @return bool
     * @throws LocalizedException
     */
    public function execute(array $params)
    {
        $this->validator->validateParams($params);

        $stagingData = $params['stagingData'];
        $entityData = $params['entityData'];

        if (!isset($stagingData['update_id']) || empty($stagingData['update_id'])) {
            $update = $this->createUpdate($stagingData);
            $this->versionManager->setCurrentVersionId($update->getId());

            $this->schedule($entityData, $update->getId());
        } else {
            $update = $this->updateRepository->get($stagingData['update_id']);
            $this->versionManager->setCurrentVersionId($update->getId());

            $update = $this->editUpdate($stagingData);

            $this->schedule(
                $entityData,
                $update->getId(),
                [
                    'origin_in' => $stagingData['update_id'],
                ]
            );
        }

        return true;
    }

    /**
     * Validate input parameters
     *
     * @param array $params
     * @return void
     * @deprecated 100.1.7 Since functionality was moved
     * @see \Magento\Staging\Model\Update\Validator
     */
    protected function validateParams(array $params)
    {
        $this->validator->validateParams($params);
    }

    /**
     * Create new update with data from request
     *
     * @param array $stagingData
     * @return \Magento\Staging\Model\Update
     */
    private function createUpdate(array $stagingData)
    {
        /** @var \Magento\Staging\Model\Update $update */
        $update = $this->updateFactory->create();
        $hydrator = $this->metadataPool->getHydrator(UpdateInterface::class);
        $hydrator->hydrate($update, $stagingData);

        $update->setIsCampaign(false);

        $this->updateRepository->save($update);

        return $update;
    }

    /**
     * Edit existing update with data from request
     *
     * Before editing update executes checks:
     *   - If update start time value was changed, then decline editing.
     *   - If update already started, then decline editing.
     *
     * @param array $stagingData
     * @return UpdateInterface
     * @throws LocalizedException if incorrect changes of datetime attribute was detected
     */
    private function editUpdate(array $stagingData)
    {
        $update = $this->updateRepository->get($stagingData['update_id']);
        $dataStart = strtotime($stagingData['start_time']);
        $updateStart = $update->getStartTime() ? strtotime($update->getStartTime()) : 0;

        $this->validator->validateUpdateStarted($update, $stagingData);
        if ($dataStart != $updateStart) {
            unset($stagingData['update_id']);
            return $this->createUpdate($stagingData);
        }
        $hydrator = $this->metadataPool->getHydrator(UpdateInterface::class);
        $hydrator->hydrate($update, $stagingData);
        $this->updateRepository->save($update);

        return $update;
    }

    /**
     * Set schedule for requested entity
     *
     * @param array $entityData
     * @param int $version
     * @param array $arguments
     * @return void
     */
    private function schedule(array $entityData, $version, array $arguments = [])
    {
        $entity = $this->entityHydrator->hydrate($entityData);

        $this->entityStaging->schedule($entity, $version, $arguments);
    }
}
