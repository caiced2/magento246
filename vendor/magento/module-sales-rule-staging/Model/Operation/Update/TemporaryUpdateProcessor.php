<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRuleStaging\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\SalesRule\Model\ResourceModel\Rule;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\SalesRule\Model\RuleFactory;

/**
 * Processes temporary updates for sales rule
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TemporaryUpdateProcessor implements \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface
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
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var Rule
     */
    private $ruleResource;

    /**
     * @param TypeResolver $typeResolver
     * @param CreateEntityVersion $createEntityVersion
     * @param ReadEntityVersion $entityVersion
     * @param VersionManager $versionManager
     * @param EntityManager $entityManager
     * @param MetadataPool $metadataPool
     * @param RuleFactory $ruleFactory
     * @param Rule $ruleResource
     */
    public function __construct(
        TypeResolver $typeResolver,
        CreateEntityVersion $createEntityVersion,
        ReadEntityVersion $entityVersion,
        VersionManager $versionManager,
        EntityManager $entityManager,
        MetadataPool $metadataPool,
        RuleFactory $ruleFactory,
        Rule $ruleResource = null
    ) {
        $this->typeResolver = $typeResolver;
        $this->createEntityVersion = $createEntityVersion;
        $this->entityVersion = $entityVersion;
        $this->versionManager = $versionManager;
        $this->entityManager = $entityManager;
        $this->metadataPool = $metadataPool;
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource ?: ObjectManager::getInstance()->get(Rule::class);
    }

    /**
     * @inheritdoc
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

        $previousEntity = $this->ruleFactory->create();
        $previousEntity = $this->entityManager->load($previousEntity, $entity->getId());
        $labels = $previousEntity->getStoreLabels();

        $this->versionManager->setCurrentVersionId($rollbackId);
        $arguments = [
            'created_in' => $rollbackId,
            'updated_in' => $nextVersionId,
            'origin_in' => $previousVersionId
        ];
        $this->createEntityVersion->execute($previousEntity, $arguments);
        $this->ruleResource->saveStoreLabels($previousEntity->getRowId(), $labels);
        $this->versionManager->setCurrentVersionId($versionId);
        return $entity;
    }
}
