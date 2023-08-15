<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;
use Magento\Framework\EntityManager\TypeResolver;

class DefaultTemporaryUpdateProcessor implements \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var CreateEntityVersion
     */
    private $createEntityVersion;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersion;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param TypeResolver $typeResolver
     * @param CreateEntityVersion $createEntityVersion
     * @param ReadEntityVersion $entityVersion
     * @param VersionManager $versionManager
     * @param EntityManager $entityManager
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        TypeResolver $typeResolver,
        CreateEntityVersion $createEntityVersion,
        ReadEntityVersion $entityVersion,
        VersionManager $versionManager,
        EntityManager $entityManager,
        MetadataPool $metadataPool
    ) {
        $this->typeResolver = $typeResolver;
        $this->createEntityVersion = $createEntityVersion;
        $this->entityVersion = $entityVersion;
        $this->versionManager = $versionManager;
        $this->entityManager = $entityManager;
        $this->metadataPool = $metadataPool;
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

        $previousVersionId = $this->entityVersion->getPreviousVersionId($entityType, $versionId, $entityId);
        $nextVersionId = $this->entityVersion->getNextVersionId($entityType, $rollbackId, $entityId);
        $this->versionManager->setCurrentVersionId($previousVersionId);

        $previousEntity = $this->getObjectManager()->create($entityType);
        $previousEntity = $this->entityManager->load($previousEntity, $entity->getId());

        $this->versionManager->setCurrentVersionId($rollbackId);
        $arguments = [
            'created_in' => $rollbackId,
            'updated_in' => $nextVersionId,
            'origin_in' => $previousVersionId
        ];
        $this->createEntityVersion->execute($previousEntity, $arguments);
        $this->versionManager->setCurrentVersionId($versionId);
        return $entity;
    }

    /**
     * Get instance of object manager
     * @return ObjectManager
     * @deprecated 100.1.0
     */
    private function getObjectManager()
    {
        if (!$this->objectManager) {
            $this->objectManager = ObjectManager::getInstance();
        }
        return $this->objectManager;
    }
}
