<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity\PeriodSync;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use Magento\Staging\Model\ResourceModel\Update as UpdateResource;
use Magento\Staging\Model\StagingList;
use Magento\Staging\Model\VersionManager;

class EntitySynchronizer
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var StagingList
     */
    private $stagingList;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var RetrieverPool
     */
    private $retrieverPool;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var EntityStaging
     */
    private $entityStaging;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * @var UpdateResource
     */
    private $updateResource;

    /**
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param StagingList $stagingList
     * @param MetadataPool $metadataPool
     * @param RetrieverPool $retrieverPool
     * @param TypeResolver $typeResolver
     * @param EntityStaging $entityStaging
     * @param CampaignValidator $campaignValidator
     * @param UpdateResource $updateResource
     */
    public function __construct(
        VersionManager $versionManager,
        UpdateRepositoryInterface $updateRepository,
        StagingList $stagingList,
        MetadataPool $metadataPool,
        RetrieverPool $retrieverPool,
        TypeResolver $typeResolver,
        EntityStaging $entityStaging,
        CampaignValidator $campaignValidator,
        UpdateResource $updateResource
    ) {
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->stagingList = $stagingList;
        $this->metadataPool = $metadataPool;
        $this->retrieverPool = $retrieverPool;
        $this->typeResolver = $typeResolver;
        $this->entityStaging = $entityStaging;
        $this->campaignValidator = $campaignValidator;
        $this->updateResource = $updateResource;
    }

    /**
     * Synchronize update with scheduled entities.
     *
     * @param int $updateId
     * @throws LocalizedException
     */
    public function execute(int $updateId): void
    {
        $previousUpdateId = $this->updateResource->getPreviousUpdateId($updateId);
        if ($previousUpdateId) {
            $this->execute($previousUpdateId);
        }

        try {
            $update = $this->updateRepository->get($updateId);
        } catch (NoSuchEntityException $e) {
            //Update is already synchronized
            return;
        }

        $initVersion = $this->versionManager->getVersion()->getId();
        try {
            foreach ($this->stagingList->getEntityTypes() as $entityType) {
                $this->synchronizeEntity($entityType, (int) $update->getId(), (int) $update->getMovedTo());
            }
        } catch (ValidatorException $e) {
            if ($update->getMovedTo()) {
                $newUpdate = $this->updateRepository->get((int) $update->getMovedTo());
                $update->setMovedTo(null);
                $this->updateRepository->save($update);
                $this->updateRepository->delete($newUpdate);
            }

            throw $e;
        } finally {
            $this->versionManager->setCurrentVersionId($initVersion);
        }

        if ($update->getMovedTo()) {
            $this->updateRepository->delete($update);
        }
    }

    /**
     * Synchronize entity type with update.
     *
     * @param string $entityType
     * @param int $oldVersionId
     * @param int $newVersionId
     * @return void
     * @throws LocalizedException
     * @throws ValidatorException
     */
    private function synchronizeEntity(string $entityType, int $oldVersionId, int $newVersionId): void
    {
        if ($oldVersionId === $newVersionId) {
            return;
        }

        $entityList = $this->getVersions($entityType, $oldVersionId);
        if (!$entityList) {
            return;
        }

        $arguments['origin_in'] = $oldVersionId;
        $newVersionId = $newVersionId ?: $oldVersionId;
        $retriever = $this->retrieverPool->getRetriever($entityType);
        $this->versionManager->setCurrentVersionId($oldVersionId);
        foreach ($entityList as $entityId) {
            $entity = $retriever->getEntity($entityId);
            $realEntityType = $this->typeResolver->resolve($entity);
            if ($realEntityType !== $entityType) {
                throw new LocalizedException(__('Repository should return instance of %s'));
            }

            if (!$this->campaignValidator->canBeScheduled($entity, $newVersionId, $oldVersionId)) {
                throw new ValidatorException(
                    __('Future Update already exists in this time range. Set a different range and try again.')
                );
            }
        }

        foreach ($entityList as $entityId) {
            $entity = $retriever->getEntity($entityId);
            $this->versionManager->setCurrentVersionId($newVersionId);
            $this->entityStaging->schedule($entity, $newVersionId, $arguments);
            $this->versionManager->setCurrentVersionId($oldVersionId);
        }
    }

    /**
     * Get all entities assigned to update ($versionId)
     *
     * @param string $entityType
     * @param int $versionId
     * @return array
     */
    private function getVersions(string $entityType, int $versionId): array
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $connection = $metadata->getEntityConnection();
        $select = $connection->select()
            ->from(['table_name' => $metadata->getEntityTable()], [$metadata->getIdentifierField()])
            ->where('created_in = ?', $versionId)
            ->setPart('disable_staging_preview', true);

        return $connection->fetchCol($select);
    }
}
