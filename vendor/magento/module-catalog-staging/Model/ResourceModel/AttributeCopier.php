<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\ResourceModel;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\Entity\ScopeResolver;

/**
 * Class for attribute copy
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeCopier
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ScopeResolver
     */
    private $scopeResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * AttributeCopier constructor.
     *
     * @param MetadataPool $metadataPool
     * @param ScopeResolver $scopeResolver
     * @param ResourceConnection $resourceConnection
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        MetadataPool $metadataPool,
        ScopeResolver $scopeResolver,
        ResourceConnection $resourceConnection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->metadataPool = $metadataPool;
        $this->scopeResolver = $scopeResolver;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get entity attributes
     *
     * @param string $entityType
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     * @throws \Exception
     */
    protected function getAttributes($entityType)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $searchResult = $this->attributeRepository->getList(
            $metadata->getEavEntityType(),
            $this->searchCriteriaBuilder->create()
        );
        return $searchResult->getItems();
    }

    /**
     * Returns link id for requested update
     *
     * @param EntityMetadataInterface $metadata
     * @param string $entityId
     * @param string $createdIn
     * @return string
     * @throws \Zend_Db_Select_Exception
     */
    private function getLinkId(EntityMetadataInterface $metadata, $entityId, $createdIn)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select();
        $select->from(['t' => $metadata->getEntityTable()], [$metadata->getLinkField()])
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $entityId)
            ->where('t.created_in <= ?', $createdIn)
            ->order('t.created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        return $connection->fetchOne($select);
    }

    /**
     * Copy attributes for staging
     *
     * @param string $entityType
     * @param array $entityData
     * @param string $from
     * @param string $to
     * @return bool
     * @throws \Exception
     */
    public function copy($entityType, $entityData, $from, $to)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $entityId = $entityData[$metadata->getIdentifierField()];
        $fromRowId = $this->getLinkId($metadata, $entityId, $from);
        $toRowId = $this->getLinkId($metadata, $entityId, $to);
        $attributes = $this->getAttributes($entityType);
        $attributeTables = [];
        foreach ($attributes as $attribute) {
            if (!$attribute->isStatic()) {
                $attributeTables[] = $attribute->getBackend()->getTable();
            }
        }
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $attributeTables = array_unique($attributeTables);
        foreach ($attributeTables as $attributeTable) {
            $select = $connection->select()
                ->from($attributeTable, '')
                ->where($metadata->getLinkField() . ' = ?', $fromRowId);
            $insertColumns = [
                'attribute_id' => 'attribute_id',
                'store_id' => 'store_id',
                $metadata->getLinkField() => new \Zend_Db_Expr($toRowId),
                'value' => 'value'
            ];
            $select->columns($insertColumns);
            $query = $select->insertFromSelect($attributeTable, array_keys($insertColumns));
            $connection->query($query);
        }

        return true;
    }
}
