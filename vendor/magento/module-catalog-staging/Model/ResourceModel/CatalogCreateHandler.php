<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\ResourceModel;

use Magento\Eav\Model\ResourceModel\CreateHandler;
use Magento\Eav\Model\ResourceModel\UpdateHandler;
use Magento\Framework\EntityManager\Operation\AttributeInterface;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class purpose is to handle catalog entity creation taking into account staging features
 */
class CatalogCreateHandler implements AttributeInterface
{
    /**
     * @var CreateHandler
     */
    protected $createHandler;

    /**
     * @var UpdateHandler
     */
    protected $updateHandler;

    /**
     * @var VersionHistoryInterface
     */
    protected $versionHistory;

    /**
     * @var AttributeCopier
     */
    private $attributeCopier;

    /**
     * CatalogCreateHandler constructor.
     *
     * @param CreateHandler $createHandler
     * @param UpdateHandler $updateHandler
     * @param VersionHistoryInterface $versionHistory
     * @param AttributeCopier $attributeCopier
     */
    public function __construct(
        CreateHandler $createHandler,
        UpdateHandler $updateHandler,
        VersionHistoryInterface $versionHistory,
        AttributeCopier $attributeCopier
    ) {
        $this->createHandler = $createHandler;
        $this->updateHandler = $updateHandler;
        $this->versionHistory = $versionHistory;
        $this->attributeCopier = $attributeCopier;
    }

    /**
     * Process catalog entity creation taking into account staging features
     *
     * In case entity is new it always must be passed to create handler without changes.
     * In case entity is version of existing entity it must be passed to update handler.
     * Attribute Copier is used to synchronize data between versioned entity and original one.
     * New entity always has `created_in` field value equal to value of VersionManager::MIN_VERSION constant.
     *
     * @param string $entityType
     * @param array $entityData
     * @param array $arguments
     * @return array
     */
    public function execute($entityType, $entityData, $arguments = [])
    {
        if (isset($arguments['origin_in'])) {
            $originId = $arguments['origin_in'];
        } elseif (isset($arguments['copy_origin_in'])) {
            $originId = $arguments['copy_origin_in'];
        } else {
            $originId = $this->versionHistory->getCurrentId();
        }
        if (!empty($entityData['default_sort_by'])) {
            $entityData['default_sort_by'] = is_array($entityData['default_sort_by']) ?
                reset($entityData['default_sort_by']) :
                    $entityData['default_sort_by'];
        }
        if (!in_array($entityData['created_in'], [VersionManager::MIN_VERSION, $originId], true)) {
            $this->attributeCopier->copy(
                $entityType,
                $entityData,
                $originId,
                $entityData['created_in']
            );
            //updating attributes of entity if this entity is not for rollback
            if (empty($arguments['is_rollback'])) {
                return $this->updateHandler->execute($entityType, $entityData, $arguments);
            }
            return $entityData;
        } else {
            return $this->createHandler->execute($entityType, $entityData, $arguments);
        }
    }
}
