<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation\Delete;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\Db\DeleteRow as DeleteEntityRow;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Model\AbstractModel;
use Magento\Staging\Model\Entity\Builder;
use Magento\Staging\Model\Entity\VersionLoader;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;

/**
 * Process overlapping updates
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateIntersectedUpdates
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
     * @var DeleteEntityRow
     */
    protected $deleteEntityRow;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var UpdateIntersectedRollbacks
     */
    protected $intersectedRollbacks;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var VersionLoader
     */
    private $versionLoader;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param TypeResolver $typeResolver
     * @param UpdateIntersectedRollbacks $intersectedRollbacks
     * @param VersionManager $versionManager
     * @param ReadEntityVersion $readEntityVersion
     * @param DeleteEntityRow $deleteEntityRow
     * @param MetadataPool $metadataPool
     * @param EntityManager $entityManager
     * @param Builder $builder
     * @param VersionLoader|null $versionLoader
     */
    public function __construct(
        TypeResolver $typeResolver,
        UpdateIntersectedRollbacks $intersectedRollbacks,
        VersionManager $versionManager,
        ReadEntityVersion $readEntityVersion,
        DeleteEntityRow $deleteEntityRow,
        MetadataPool $metadataPool,
        EntityManager $entityManager,
        Builder $builder,
        VersionLoader $versionLoader = null
    ) {
        $this->typeResolver = $typeResolver;
        $this->intersectedRollbacks = $intersectedRollbacks;
        $this->versionManager = $versionManager;
        $this->readEntityVersion = $readEntityVersion;
        $this->deleteEntityRow = $deleteEntityRow;
        $this->metadataPool = $metadataPool;
        $this->entityManager = $entityManager;
        $this->builder = $builder;
        $this->versionLoader = $versionLoader ?: ObjectManager::getInstance()->get(VersionLoader::class);
    }

    /**
     * Process overlapped updates
     *
     * @param object $entity
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    public function execute($entity)
    {
        $entityType = $this->typeResolver->resolve($entity);
        if ($this->versionManager->getCurrentVersion()->getRollbackId() !== null) {
            $this->processTemporaryUpdateDelete($entityType, $entity);
        } else {
            $this->processPermanentUpdateDelete($entityType, $entity);
        }
    }

    /**
     * Process permanent update delete
     *
     * @param string $entityType
     * @param AbstractModel $entity
     * @return void
     * @throws \Exception
     */
    private function processPermanentUpdateDelete($entityType, $entity)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $entityData = $hydrator->extract($entity);
        $identifierField = $metadata->getIdentifierField();
        $nextVersionId = $this->getNextVersionId($entityType, $entityData, $identifierField);

        if ($nextVersionId === VersionManager::MAX_VERSION) {
            $nextVersion = [
                'created_in' => $nextVersionId,
                $metadata->getIdentifierField() => $entityData[$identifierField]
            ];
        } else {
            $nextVersion = $this->getEntityVersion($entity, $entityType, $nextVersionId);
        }

        // Update prev permanent
        $this->updatePreviousVersion(
            ['updated_in' => $nextVersion['created_in']],
            $this->getPreviousVersionId($entityType, $entityData, $identifierField),
            $nextVersion,
            $entityType
        );

        $prevPermanentId = $this->readEntityVersion->getPreviousPermanentVersionId(
            $entityType,
            $entityData['created_in'],
            $entityData[$identifierField]
        );
        $nextPermanentId = $this->readEntityVersion->getNextPermanentVersionId(
            $entityType,
            $entityData['created_in'],
            $entityData[$identifierField]
        );

        $prevPermanentEntity = $this->versionLoader->load(
            $entity,
            $entityData[$identifierField],
            $prevPermanentId
        );
        $this->intersectedRollbacks->execute(
            $prevPermanentEntity,
            $nextPermanentId
        );
    }

    /**
     * Process temporary update delete
     *
     * @param string $entityType
     * @param AbstractModel $entity
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    private function processTemporaryUpdateDelete($entityType, $entity)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $entityData = $hydrator->extract($entity);
        $nextVersionId = $this->getNextVersionId($entityType, $entityData, $metadata->getIdentifierField());
        $nextVersion = $this->getEntityVersion($entity, $entityType, $nextVersionId);
        if (!$nextVersion) {
            $nextVersion = [
                'updated_in' => $nextVersionId,
                $metadata->getIdentifierField() => $entityData[$metadata->getIdentifierField()],
            ];
        }
        $this->updatePreviousVersion(
            ['updated_in' => $nextVersion['updated_in']],
            $this->getPreviousVersionId($entityType, $entityData, $metadata->getIdentifierField()),
            $nextVersion,
            $entityType
        );

        if (isset($nextVersion[$metadata->getLinkField()])) {
            // Remove rollback for update
            $this->deleteEntityRow->execute($entityType, $nextVersion);
        }
    }

    /**
     * Get entity version
     *
     * @param AbstractModel $entity
     * @param string $entityType
     * @param int $versionId
     * @return array
     * @throws \Exception
     * @throws \Zend_Db_Select_Exception
     */
    private function getEntityVersion($entity, $entityType, $versionId)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);

        $select = $metadata->getEntityConnection()
            ->select()
            ->from(['entity_table' => $metadata->getEntityTable()])
            ->where('created_in = ?', $versionId)
            ->where($metadata->getIdentifierField() . ' = ?', $entity[$metadata->getIdentifierField()])
            ->setPart('disable_staging_preview', true);

        return $metadata->getEntityConnection()->fetchRow($select) ?: [];
    }

    /**
     * Update previous version
     *
     * @param array $bind
     * @param int $prevVersionId
     * @param array $nextVersionData
     * @param string $entityType
     * @return void
     * @throws \Exception
     */
    private function updatePreviousVersion($bind, $prevVersionId, $nextVersionData, $entityType)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);

        $metadata->getEntityConnection()->update(
            $metadata->getEntityTable(),
            $bind,
            [
                $metadata->getIdentifierField() . ' = ?' => $nextVersionData[$metadata->getIdentifierField()],
                'created_in = ?' => $prevVersionId
            ]
        );
    }

    /**
     * Get next version Id
     *
     * @param string $entityType
     * @param array $entityData
     * @param string $identifierField
     * @return int
     */
    private function getNextVersionId($entityType, $entityData, $identifierField)
    {
        return $this->readEntityVersion->getNextVersionId(
            $entityType,
            $entityData['created_in'],
            $entityData[$identifierField]
        );
    }

    /**
     * Get previous version Id
     *
     * @param string $entityType
     * @param array $entityData
     * @param string $identifierField
     * @return int|string
     */
    private function getPreviousVersionId($entityType, $entityData, $identifierField)
    {
        return $this->readEntityVersion->getPreviousVersionId(
            $entityType,
            $entityData['created_in'],
            $entityData[$identifierField]
        );
    }
}
