<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Action;

use Magento\Framework\Model\ResourceModel\Db\UpdateEntityRow;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;

/**
 * Class UpdateMain
 */
class UpdateVersion
{
    /**
     * @var UpdateEntityRow
     */
    protected $updateEntityRow;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var ReadEntityVersion
     */
    protected $entityVersion;

    /**
     * @param UpdateEntityRow $updateEntityRow
     * @param MetadataPool $metadataPool
     * @param ReadEntityVersion $entityVersion
     * @param VersionManager $versionManager
     */
    public function __construct(
        UpdateEntityRow $updateEntityRow,
        MetadataPool $metadataPool,
        ReadEntityVersion $entityVersion,
        VersionManager $versionManager
    ) {
        $this->updateEntityRow = $updateEntityRow;
        $this->metadataPool = $metadataPool;
        $this->entityVersion = $entityVersion;
        $this->versionManager = $versionManager;
    }

    /**
     * @param string $entityType
     * @param int $identifier
     * @throws \Exception
     * @return void
     */
    public function execute($entityType, $identifier)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);

        $stagingData = [
            $metadata->getLinkField() => $this->entityVersion->getPreviousVersionRowId($entityType, $identifier),
            'updated_in' => $this->versionManager->getVersion()->getId()
        ];

        $this->updateEntityRow->execute($entityType, $stagingData);
    }
}
