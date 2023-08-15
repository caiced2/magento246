<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Operation;

use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Framework\EntityManager\Operation\CreateInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\Operation\Update;
use Magento\Staging\Model\Operation\Create as StagingCreateOperation;

/**
 * Class Create
 *
 * Saves Catalog Price Rule with staging information.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create implements CreateInterface
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var Update
     */
    protected $operationUpdate;

    /**
     * @var UpdateInterfaceFactory
     */
    protected $updateFactory;

    /**
     * @var StagingCreateOperation
     */
    private $operationCreate;

    /**
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param Update $operationUpdate
     * @param UpdateInterfaceFactory $updateFactory
     * @param StagingCreateOperation $operationCreate
     */
    public function __construct(
        VersionManager $versionManager,
        UpdateRepositoryInterface $updateRepository,
        Update $operationUpdate,
        UpdateInterfaceFactory $updateFactory,
        StagingCreateOperation $operationCreate
    ) {
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->operationUpdate = $operationUpdate;
        $this->updateFactory = $updateFactory;
        $this->operationCreate = $operationCreate;
    }

    /**
     * Create entity.
     *
     * @param \Magento\CatalogRule\Api\Data\RuleInterface $entity
     * @param array $arguments
     * @return object
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        return $this->operationCreate->execute($entity, $arguments);
    }
}
