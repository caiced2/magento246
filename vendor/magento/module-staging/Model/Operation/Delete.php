<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation;

use Magento\Framework\EntityManager\Operation\DeleteInterface;
use Magento\Framework\EntityManager\Operation\Delete\DeleteExtensions as DeleteRelation;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\Delete\DeleteMain;
use Magento\Framework\EntityManager\Operation\Delete\DeleteAttributes as DeleteExtension;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;
use Magento\Framework\EntityManager\Sequence\SequenceManager;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedUpdates;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\EntityManager\TypeResolver;

/**
 * Class Delete
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Delete implements DeleteInterface
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var DeleteMain
     */
    protected $deleteMain;

    /**
     * @var DeleteExtension
     */
    protected $deleteExtension;

    /**
     * @var DeleteRelation
     */
    protected $deleteRelation;

    /**
     * @var SequenceManager
     */
    protected $sequenceManager;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var UpdateIntersectedUpdates
     */
    protected $updateIntersectedUpdates;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @param TypeResolver $typeResolver
     * @param MetadataPool $metadataPool
     * @param DeleteMain $deleteMain
     * @param DeleteExtension $deleteExtension
     * @param DeleteRelation $deleteRelation
     * @param VersionManager $versionManager
     * @param SequenceManager $sequenceManager
     * @param UpdateIntersectedUpdates $updateIntersectedUpdates
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TypeResolver $typeResolver,
        MetadataPool $metadataPool,
        DeleteMain $deleteMain,
        DeleteExtension $deleteExtension,
        DeleteRelation $deleteRelation,
        VersionManager $versionManager,
        SequenceManager $sequenceManager,
        UpdateIntersectedUpdates $updateIntersectedUpdates,
        ResourceConnection $resourceConnection,
        EventManager $eventManager
    ) {
        $this->typeResolver = $typeResolver;
        $this->metadataPool = $metadataPool;
        $this->deleteMain = $deleteMain;
        $this->deleteExtension = $deleteExtension;
        $this->deleteRelation = $deleteRelation;
        $this->versionManager = $versionManager;
        $this->sequenceManager = $sequenceManager;
        $this->updateIntersectedUpdates = $updateIntersectedUpdates;
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param array $arguments
     * @return true
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        $entityType = $this->typeResolver->resolve($entity);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $connection->beginTransaction();
        try {
            $this->eventManager->dispatch(
                'entity_manager_delete_before',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );
            $this->eventManager->dispatchEntityEvent($entityType, 'delete_before', ['entity' => $entity]);
            $entityData = $hydrator->extract($entity);
            if (!isset($entityData[$metadata->getIdentifierField()])) {
                throw new \Exception('Could not delete entity. Identifier field does not specified');
            }
            $this->deleteRelation->execute($entity, $arguments);
            $this->deleteExtension->execute($entity, $arguments);
            $this->deleteMain->execute($entity, $arguments);
            $this->updateIntersectedUpdates->execute($entity);

            if (!$this->versionManager->isPreviewVersion() && !$this->isUnscheduleOperation($arguments)) {
                $this->sequenceManager->delete($entityType, $entityData[$metadata->getIdentifierField()]);
            }
            $this->eventManager->dispatchEntityEvent($entityType, 'delete_after', ['entity' => $entity]);
            $this->eventManager->dispatch(
                'entity_manager_delete_after',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * Checks whether we try to unschedule scheduled update or delete whole entity.
     *
     * Needs to prevent deleting original entity when the scheduled update
     * is currently active and should be removed.
     *
     * @param array $arguments
     * @return bool
     */
    private function isUnscheduleOperation(array $arguments): bool
    {
        return array_key_exists('created_in', $arguments);
    }
}
