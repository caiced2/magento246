<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation\Update;

use Magento\Framework\EntityManager\Operation\Create\CreateExtensions as CreateRelation;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\Create\CreateMain;
use Magento\Framework\EntityManager\Operation\Create\CreateAttributes as CreateExtension;
use Magento\Framework\EntityManager\TypeResolver;

class CreateEntityVersion
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var CreateMain
     */
    protected $createMain;

    /**
     * @var CreateExtension
     */
    protected $createExtension;

    /**
     * @var CreateRelation
     */
    protected $createRelation;

    /**
     * @param TypeResolver $typeResolver
     * @param MetadataPool $metadataPool
     * @param CreateMain $createMain
     * @param CreateRelation $createRelation
     * @param CreateExtension $createExtension
     */
    public function __construct(
        TypeResolver $typeResolver,
        MetadataPool $metadataPool,
        CreateMain $createMain,
        CreateRelation $createRelation,
        CreateExtension $createExtension
    ) {
        $this->typeResolver = $typeResolver;
        $this->metadataPool = $metadataPool;
        $this->createMain = $createMain;
        $this->createRelation = $createRelation;
        $this->createExtension = $createExtension;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return void
     * @throws \Exception
     */
    public function execute($entity, $arguments)
    {
        $entityType = $this->typeResolver->resolve($entity);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $entityData = $hydrator->extract($entity);
        $entityData[$metadata->getLinkField()] = null;

        $entity = $hydrator->hydrate($entity, $entityData);

        $this->createMain->execute($entity, $arguments);
        $this->createExtension->execute($entity, $arguments);
        $this->createRelation->execute($entity, $arguments);
    }
}
