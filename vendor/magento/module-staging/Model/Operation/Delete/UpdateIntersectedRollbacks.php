<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation\Delete;

use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;

class UpdateIntersectedRollbacks
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var ReadEntityVersion
     */
    protected $readEntityVersion;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateEntityVersion
     */
    private $updateEntityVersion;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var HydratorPool
     */
    private $hydratorPool;

    /**
     * Initialize dependencies.
     *
     * @param TypeResolver $typeResolver
     * @param MetadataPool $metadataPool
     * @param HydratorPool $hydratorPool
     * @param ReadEntityVersion $readEntityVersion
     * @param VersionManager $versionManager
     * @param UpdateEntityVersion $updateEntityVersion
     */
    public function __construct(
        TypeResolver $typeResolver,
        MetadataPool $metadataPool,
        HydratorPool $hydratorPool,
        ReadEntityVersion $readEntityVersion,
        VersionManager $versionManager,
        UpdateEntityVersion $updateEntityVersion
    ) {
        $this->typeResolver = $typeResolver;
        $this->readEntityVersion = $readEntityVersion;
        $this->versionManager = $versionManager;
        $this->updateEntityVersion = $updateEntityVersion;
        $this->metadataPool = $metadataPool;
        $this->hydratorPool = $hydratorPool;
    }

    /**
     * Update intersected rollbacks with new update data
     *
     * @param object $entity
     * @param int $endVersionId
     * @return void
     */
    public function execute($entity, $endVersionId)
    {
        $entityType = $this->typeResolver->resolve($entity);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->hydratorPool->getHydrator($entityType);
        $entityData = $hydrator->extract($entity);
        $entityId = $entityData[$metadata->getIdentifierField()];
        $createdIn = $entityData['created_in'];
        $intersectedRollbacks = $this->readEntityVersion->getRollbackVersionIds(
            $entityType,
            $createdIn,
            $endVersionId,
            $entityId
        );
        $initVersion = $this->versionManager->getVersion()->getId();
        foreach ($intersectedRollbacks as $rollbackId) {
            $originEntity = clone $entity;
            $this->versionManager->setCurrentVersionId($rollbackId);
            $arguments = [
                $metadata->getLinkField() => $this->readEntityVersion->getCurrentVersionRowId($entityType, $entityId),
                'created_in' => $rollbackId,
                'updated_in' => $this->readEntityVersion->getNextVersionId($entityType, $rollbackId, $entityId),
                'is_rollback' => true,
                'copy_origin_in' => $createdIn
            ];
            $this->updateEntityVersion->execute($originEntity, $arguments);
        }
        $this->versionManager->setCurrentVersionId($initVersion);
    }
}
