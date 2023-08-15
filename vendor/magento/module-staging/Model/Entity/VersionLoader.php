<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Staging\Model\VersionManager;

/**
 * Class for loading specific version of an entity.
 */
class VersionLoader
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
     * @var Builder
     */
    private $builder;

    /**
     * @param EntityManager  $entityManager
     * @param VersionManager $versionManager
     * @param Builder        $builder
     */
    public function __construct(
        EntityManager $entityManager,
        VersionManager $versionManager,
        Builder $builder
    ) {
        $this->entityManager = $entityManager;
        $this->versionManager = $versionManager;
        $this->builder = $builder;
    }

    /**
     * Load certain version of an entity.
     *
     * @param object $prototype
     * @param string $identifier
     * @param int    $versionId
     *
     * @return object
     */
    public function load(
        $prototype,
        string $identifier,
        int $versionId
    ) {
        //Applying given version to load that version of the entity
        $currentVersion = $this->versionManager->getCurrentVersion()->getId();
        $this->versionManager->setCurrentVersionId($versionId);

        //Loading entity
        /** @var object $entity */
        $entity = $this->entityManager->load(clone $prototype, $identifier);
        $entity = $this->builder->build($entity);

        //Reverting to the current version.
        $this->versionManager->setCurrentVersionId($currentVersion);

        return $entity;
    }
}
