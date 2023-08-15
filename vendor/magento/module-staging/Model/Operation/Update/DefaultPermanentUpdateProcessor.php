<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Staging\Model\Entity\VersionLoader;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedRollbacks;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Model\VersionManager;

class DefaultPermanentUpdateProcessor implements \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersion;

    /**
     * @var UpdateIntersectedRollbacks
     */
    private $updateIntersectedUpdates;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var VersionLoader
     */
    private $versionLoader;

    /**
     * @param TypeResolver $typeResolver
     * @param ReadEntityVersion $entityVersion
     * @param UpdateIntersectedRollbacks $updateIntersectedUpdates
     * @param MetadataPool $metadataPool
     * @param VersionManager|null $versionManager
     * @param VersionLoader|null $versionLoader
     */
    public function __construct(
        TypeResolver $typeResolver,
        ReadEntityVersion $entityVersion,
        UpdateIntersectedRollbacks $updateIntersectedUpdates,
        MetadataPool $metadataPool,
        VersionManager $versionManager = null,
        VersionLoader $versionLoader = null
    ) {
        $this->typeResolver = $typeResolver;
        $this->entityVersion = $entityVersion;
        $this->updateIntersectedUpdates = $updateIntersectedUpdates;
        $this->metadataPool = $metadataPool;
        $this->versionManager = $versionManager ?: ObjectManager::getInstance()->get(VersionManager::class);
        $this->versionLoader = $versionLoader ?: ObjectManager::getInstance()->get(VersionLoader::class);
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity, $versionId, $rollbackId = null)
    {
        $entityType = $this->typeResolver->resolve($entity);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $entityData = $hydrator->extract($entity);
        $entityId = $entityData[$metadata->getIdentifierField()];

        if ($rollbackId) {
            $entity = $this->versionLoader->load(
                $entity,
                $entityId,
                $this->entityVersion->getPreviousPermanentVersionId(
                    $entityType,
                    $versionId,
                    $entityId
                )
            );
        }
        $nextVersionId = $this->entityVersion->getNextVersionId($entityType, $versionId, $entityId);
        $nextPermanentVersionId = $this->entityVersion->getNextPermanentVersionId($entityType, $versionId, $entityId);

        if ($nextVersionId !== $nextPermanentVersionId) {
            $this->updateIntersectedUpdates->execute($entity, $nextPermanentVersionId);
        }

        if ($rollbackId) {
            $this->versionManager->setCurrentVersionId($versionId);
        }
    }
}
