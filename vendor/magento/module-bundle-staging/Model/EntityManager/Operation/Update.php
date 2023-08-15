<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BundleStaging\Model\EntityManager\Operation;

use Magento\Framework\EntityManager\Operation\CreateInterface;
use Magento\Framework\EntityManager\Operation\UpdateInterface;
use Magento\Framework\EntityManager\Operation\CheckIfExistsInterface;

/**
 * An operation that updates existing entity in a DB.
 */
class Update implements UpdateInterface
{
    /**
     * @var \Magento\Framework\EntityManager\TypeResolver
     */
    private $typeResolver;

    /**
     * @var \Magento\Framework\EntityManager\OperationPool
     */
    private $operationPool;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\EntityManager\TypeResolver $typeResolver
     * @param \Magento\Framework\EntityManager\OperationPool $operationPool
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\EntityManager\TypeResolver $typeResolver,
        \Magento\Framework\EntityManager\OperationPool $operationPool,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->typeResolver = $typeResolver;
        $this->operationPool = $operationPool;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        $entityType = $this->typeResolver->resolve($entity);

        if ($this->isExists($entity, $entityType)) {
            $operation = $this->operationPool->getOperation($entityType, 'updateUpdate');

            if (!($operation instanceof UpdateInterface)) {
                throw new \LogicException(
                    get_class($operation) . ' must implement ' . UpdateInterface::class
                );
            }
        } else {
            $operation = $this->operationPool->getOperation($entityType, 'updateCreate');

            if (!($operation instanceof CreateInterface)) {
                throw new \LogicException(
                    get_class($operation) . ' must implement ' . UpdateInterface::class
                );
            }
        }

        try {
            $entity = $operation->execute($entity, $arguments);
        } catch (\Exception $e) {
            $this->logger->critical($e);

            throw $e;
        }

        return $entity;
    }

    /**
     * Checks if an entity exists in a DB.
     *
     * @param object $entity
     * @param string $entityType
     *
     * @return bool
     *
     * @throws \LogicException
     */
    private function isExists($entity, $entityType)
    {
        $operation = $this->operationPool->getOperation($entityType, 'updateCheckIfExists');

        if (!($operation instanceof CheckIfExistsInterface)) {
            throw new \LogicException(
                get_class($operation) . ' must implement ' . CheckIfExistsInterface::class
            );
        }

        return $operation->execute($entity);
    }
}
