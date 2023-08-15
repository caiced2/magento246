<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation;

use Magento\Framework\EntityManager\MetadataPool;

use Magento\Framework\EntityManager\Operation\CreateInterface;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;
use Magento\Framework\EntityManager\Sequence\SequenceManager;
use Magento\Framework\EntityManager\Operation\Create\CreateMain;
use Magento\Framework\EntityManager\Operation\Create\CreateAttributes;
use Magento\Framework\EntityManager\Operation\Create\CreateExtensions;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Create
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create implements CreateInterface
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var CreateExtensions
     */
    private $createExtensions;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var CreateMain
     */
    private $createMain;

    /**
     * @var CreateAttributes
     */
    private $createAttributes;

    /**
     * @var SequenceManager
     */
    private $sequenceManager;

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
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @param CreateMain $createMain
     * @param CreateAttributes $createAttributes
     * @param CreateExtensions $createExtensions
     * @param SequenceManager $sequenceManager
     */
    public function __construct(
        TypeResolver $typeResolver,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        EventManager $eventManager,
        CreateMain $createMain,
        CreateAttributes $createAttributes,
        CreateExtensions $createExtensions,
        SequenceManager $sequenceManager
    ) {
        $this->typeResolver = $typeResolver;
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
        $this->createMain = $createMain;
        $this->createAttributes = $createAttributes;
        $this->createExtensions = $createExtensions;
        $this->sequenceManager = $sequenceManager;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return object
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $entityType = $this->typeResolver->resolve($entity);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $connection->beginTransaction();
        try {
            $this->eventManager->dispatch(
                'entity_manager_save_before',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );
            $this->eventManager->dispatchEntityEvent($entityType, 'save_before', ['entity' => $entity]);
            $entityData = $hydrator->extract($entity);
            if (isset($entityData[$metadata->getIdentifierField()]) && $entityData[$metadata->getIdentifierField()]) {
                $this->sequenceManager->force($entityType, $entityData[$metadata->getIdentifierField()]);
            } else {
                $entityData[$metadata->getIdentifierField()] = $metadata->generateIdentifier();
            }

            $entityData[$metadata->getLinkField()] = null;
            $entity = $hydrator->hydrate($entity, $entityData);

            $stagingData = [
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION
            ];

            $entity = $this->createMain->execute($entity, $stagingData);
            $entity = $this->createAttributes->execute($entity, $stagingData);
            $entity = $this->createExtensions->execute($entity);

            $this->eventManager->dispatchEntityEvent($entityType, 'save_after', ['entity' => $entity]);
            $this->eventManager->dispatch(
                'entity_manager_save_after',
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
        return $entity;
    }
}
