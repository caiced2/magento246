<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;

/**
 * Class VersionInfoProvider
 */
class VersionInfoProvider
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @var HydratorPool
     */
    private $hydratorPool;

    /**
     * @var VersionInfoFactory
     */
    private $versionInfoFactory;

    /**
     * Initialize dependencies.
     *
     * @param TypeResolver $typeResolver
     * @param MetadataPool $metadataPool
     * @param HydratorPool $hydratorPool
     * @param ResourceConnection $resourceConnection
     * @param VersionHistoryInterface $versionHistory
     * @param VersionInfoFactory $versionInfoFactory
     */
    public function __construct(
        TypeResolver $typeResolver,
        MetadataPool $metadataPool,
        HydratorPool $hydratorPool,
        ResourceConnection $resourceConnection,
        VersionHistoryInterface $versionHistory,
        VersionInfoFactory $versionInfoFactory
    ) {
        $this->typeResolver = $typeResolver;
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->versionHistory = $versionHistory;
        $this->hydratorPool = $hydratorPool;
        $this->versionInfoFactory = $versionInfoFactory;
    }

    /**
     * @param EntityMetadataInterface $metadata
     * @param string $identifier
     * @return array
     */
    private function getActiveRowInfo(EntityMetadataInterface $metadata, $identifier)
    {
        $identifierField = $metadata->getIdentifierField();
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        return $connection->fetchRow(
            $connection
                ->select()
                ->from(
                    ['entity_table' => $metadata->getEntityTable()],
                    [
                        $metadata->getIdentifierField(),
                        $metadata->getLinkField(),
                        'created_in',
                        'updated_in'
                    ]
                )
                ->where($identifierField . ' = ?', $identifier)
        );
    }

    /**
     * @param EntityMetadataInterface $metadata
     * @param string $identifier
     * @param string $version
     * @return array
     */
    private function getRowInfo(EntityMetadataInterface $metadata, $identifier, $version)
    {
        $identifierField = $metadata->getIdentifierField();
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        return $connection->fetchRow(
            $connection->select()
                ->from(
                    ['entity_table' => $metadata->getEntityTable()],
                    [
                        $metadata->getIdentifierField(),
                        $metadata->getLinkField(),
                        'created_in',
                        'updated_in'
                    ]
                )
                ->where($identifierField . ' = ?', $identifier)
                ->where('created_in = ?', $version)
                ->setPart('disable_staging_preview', true)
        );
    }

    /**
     * @param object $entity
     * @param string|null $version
     * @return VersionInfo
     * @throws \Exception
     */
    public function getVersionInfo($entity, $version = null)
    {
        $entityType = $this->typeResolver->resolve($entity);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->hydratorPool->getHydrator($entityType);
        $data = $hydrator->extract($entity);
        $identifierField = $metadata->getIdentifierField();
        $linkField = $metadata->getLinkField();
        if (!isset($data[$identifierField])) {
            throw new \Exception('Invalid entity');
        }
        $identifier = $data[$metadata->getIdentifierField()];
        if (($version == null) || ($this->versionHistory->getCurrentId() == $version)) {
            $data = $this->getActiveRowInfo($metadata, $identifier);
        } else {
            $data = $this->getRowInfo($metadata, $identifier, $version);
        }
        return $this->versionInfoFactory->create(
            [
                'rowId' => isset($data[$linkField]) ? $data[$linkField] : null,
                'identifier' => isset($data[$identifierField]) ? $data[$identifierField] : null,
                'createdIn' => isset($data['created_in']) ? $data['created_in'] : null,
                'updatedIn' => isset($data['updated_in']) ? $data['updated_in'] : null
            ]
        );
    }
}
