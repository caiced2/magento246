<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BundleStaging\Model\EntityManager\Operation\Update;

/**
 * An operation that creates new entity in a DB.
 */
class Create implements \Magento\Framework\EntityManager\Operation\CreateInterface
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\EntityManager\TypeResolver
     */
    private $typeResolver;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\EntityManager\EventManager
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\EntityManager\Operation\Create\CreateMain
     */
    private $createMain;

    /**
     * @var \Magento\Framework\EntityManager\Operation\Create\CreateAttributes
     */
    private $createAttributes;

    /**
     * @var \Magento\Framework\EntityManager\Operation\Create\CreateExtensions
     */
    private $createExtensions;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\Framework\EntityManager\TypeResolver $typeResolver
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\EntityManager\EventManager $eventManager
     * @param \Magento\Framework\EntityManager\Operation\Create\CreateMain $createMain
     * @param \Magento\Framework\EntityManager\Operation\Create\CreateAttributes $createAttributes
     * @param \Magento\Framework\EntityManager\Operation\Create\CreateExtensions $createExtensions
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\EntityManager\TypeResolver $typeResolver,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\EntityManager\EventManager $eventManager,
        \Magento\Framework\EntityManager\Operation\Create\CreateMain $createMain,
        \Magento\Framework\EntityManager\Operation\Create\CreateAttributes $createAttributes,
        \Magento\Framework\EntityManager\Operation\Create\CreateExtensions $createExtensions,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->metadataPool = $metadataPool;
        $this->typeResolver = $typeResolver;
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
        $this->createMain = $createMain;
        $this->createAttributes = $createAttributes;
        $this->createExtensions = $createExtensions;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        $entityType = $this->typeResolver->resolve($entity);
        $metadata = $this->metadataPool->getMetadata($entityType);

        $connection = $this->resourceConnection->getConnectionByName(
            $metadata->getEntityConnectionName()
        );

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

            $entity = $this->createMain->execute($entity, $arguments);
            $entity = $this->createAttributes->execute($entity, $arguments);
            $entity = $this->createExtensions->execute($entity, $arguments);

            $this->eventManager->dispatchEntityEvent($entityType, 'save_after', ['entity' => $entity]);

            $this->eventManager->dispatch(
                'entity_manager_save_after',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );

            $connection->commit();
        } catch (\Magento\Framework\DB\Adapter\DuplicateException $e) {
            $connection->rollBack();
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\AlreadyExistsException(
                new \Magento\Framework\Phrase('Unique constraint violation found'),
                $e
            );
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e);

            throw $e;
        }

        return $entity;
    }
}
