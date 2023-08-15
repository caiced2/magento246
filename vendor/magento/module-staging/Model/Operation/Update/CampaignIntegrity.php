<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionInfoProvider;

/**
 * Class CampaignIntegrity
 */
class CampaignIntegrity
{
    /**
     * @var PermanentUpdateProcessorPool
     */
    private $permanentUpdateProcessorPool;

    /**
     * @var TemporaryUpdateProcessorPool
     */
    private $temporaryUpdateProcessorPool;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var VersionInfoProvider
     */
    private $versionInfoProvider;

    /**
     * CampaignIntegrity constructor.
     *
     * @param PermanentUpdateProcessorPool $permanentUpdateProcessorPool
     * @param TemporaryUpdateProcessorPool $temporaryUpdateProcessorPool
     * @param TypeResolver $typeResolver
     * @param VersionInfoProvider|null $versionProvider
     */
    public function __construct(
        PermanentUpdateProcessorPool $permanentUpdateProcessorPool,
        TemporaryUpdateProcessorPool $temporaryUpdateProcessorPool,
        TypeResolver $typeResolver,
        VersionInfoProvider $versionProvider = null
    ) {
        $this->permanentUpdateProcessorPool = $permanentUpdateProcessorPool;
        $this->temporaryUpdateProcessorPool = $temporaryUpdateProcessorPool;
        $this->typeResolver = $typeResolver;
        $this->versionInfoProvider = $versionProvider ?: ObjectManager::getInstance()->get(VersionInfoProvider::class);
    }

    /**
     * @param UpdateInterface $update
     * @param object $entity
     * @return void
     * @throws \Exception
     */
    public function synchronizeAffectedCampaigns(UpdateInterface $update, $entity)
    {
        $version = $this->versionInfoProvider->getVersionInfo($entity, $update->getId());
        $entityType = $this->typeResolver->resolve($entity);
        //The update doesn't have a rollback (it's a permanent update or it's rollback ID isn't equal to it's updatedIn
        //(it's a permanent update made temporary)
        if (!$update->getRollbackId() || (int)$version->getUpdatedIn() !== (int)$update->getRollbackId()) {
            $processor = $this->permanentUpdateProcessorPool->getProcessor($entityType);
            $processor->process($entity, $update->getId(), $update->getRollbackId());
        }
    }

    /**
     * @param UpdateInterface $update
     * @param object $entity
     * @return void
     * @throws \Exception
     */
    public function createRollbackPoint(UpdateInterface $update, $entity)
    {
        $entityType = $this->typeResolver->resolve($entity);
        if ($update->getRollbackId()) {
            $processor = $this->temporaryUpdateProcessorPool->getProcessor($entityType);
            $processor->process($entity, $update->getId(), $update->getRollbackId());
        }
    }
}
