<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Operation\Update;

use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Entity\Builder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogStaging\Model\ResourceModel\Product\Price\TierPriceCopier;

/**
 * Processor for creating update product.
 */
class TemporaryUpdateProcessor implements \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersion;

    /**
     * @var CreateEntityVersion
     */
    private $createEntityVersion;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ProductLinkRepositoryInterface
     */
    private $linkRepository;

    /**
     * @var TierPriceCopier
     */
    private $tierPriceCopier;

    /**
     * @param EntityManager $entityManager
     * @param VersionManager $versionManager
     * @param ReadEntityVersion $entityVersion
     * @param CreateEntityVersion $createEntityVersion
     * @param Builder $builder
     * @param Helper $initializationHelper
     * @param ProductLinkRepositoryInterface $linkRepository
     * @param TierPriceCopier $tierPriceCopier
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        EntityManager $entityManager,
        VersionManager $versionManager,
        ReadEntityVersion $entityVersion,
        CreateEntityVersion $createEntityVersion,
        Builder $builder,
        Helper $initializationHelper,
        ProductLinkRepositoryInterface $linkRepository,
        TierPriceCopier $tierPriceCopier = null
    ) {
        $this->entityManager = $entityManager;
        $this->versionManager = $versionManager;
        $this->entityVersion = $entityVersion;
        $this->createEntityVersion = $createEntityVersion;
        $this->builder = $builder;
        $this->linkRepository = $linkRepository;
        $this->tierPriceCopier = $tierPriceCopier ??
            \Magento\Framework\App\ObjectManager::getInstance()->get(TierPriceCopier::class);
    }

    /**
     * @inheritdoc
     */
    public function process($entity, $versionId, $rollbackId = null)
    {
        $previousVersionId = $this->entityVersion->getPreviousVersionId(
            ProductInterface::class,
            $versionId,
            $entity->getId()
        );
        $nextVersionId = $this->entityVersion->getNextVersionId(ProductInterface::class, $rollbackId, $entity->getId());
        $this->versionManager->setCurrentVersionId($previousVersionId);

        /** @var Product $previousEntity */
        /** @var Product $entity */
        $previousEntity = clone $entity;
        $previousEntity->unsetData();
        $previousEntity->setId($entity->getId());
        $previousEntity->setData('_edit_mode', $entity->getData('_edit_mode'));
        $this->loadEntity($previousEntity);
        $this->buildEntity($previousEntity);

        $previousLinks = $previousEntity->getProductLinks();
        $this->versionManager->setCurrentVersionId($rollbackId);

        $arguments = [
            'created_in' => $rollbackId,
            'updated_in' => $nextVersionId,
            'origin_in' => $previousVersionId,
            'is_rollback' => true
        ];

        $this->createEntityVersion->execute($previousEntity, $arguments);
        $this->tierPriceCopier->copy($previousEntity);

        foreach ($previousLinks as $link) {
            $this->linkRepository->save($link);
        }

        $this->versionManager->setCurrentVersionId($versionId);
        return $entity;
    }

    /**
     * Load product entity.
     *
     * @param object $entity
     * @return void
     */
    public function loadEntity($entity)
    {
        $entity->setProductLinks();
        $this->entityManager->load($entity, $entity->getId());
    }

    /**
     * Build staging entity from object.
     *
     * @param object $entity
     *
     * @return void
     */
    public function buildEntity($entity)
    {
        $entity = $this->builder->build($entity);
        $entity->setRowId(null);
    }
}
