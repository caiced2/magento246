<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BundleImportExportStaging\Plugin\BundleImportExport\Model\Import\Product\Type\Bundle;

/**
 * A plugin for a bundle product relations data saver.
 */
class RelationsDataSaver
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\EntityManager\Sequence\SequenceManager
     */
    private $sequenceManager;

    /**
     * @var \Magento\Framework\EntityManager\Sequence\SequenceRegistry
     */
    private $sequenceRegistry;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\Framework\EntityManager\Sequence\SequenceManager $sequenceManager
     * @param \Magento\Framework\EntityManager\Sequence\SequenceRegistry $sequenceRegistry
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\EntityManager\Sequence\SequenceManager $sequenceManager,
        \Magento\Framework\EntityManager\Sequence\SequenceRegistry $sequenceRegistry,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->metadataPool = $metadataPool;
        $this->sequenceManager = $sequenceManager;
        $this->sequenceRegistry = $sequenceRegistry;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Applies sequence identifiers to given options before save.
     *
     * @param \Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver $subject
     * @param array $options
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveOptions(
        \Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver $subject,
        array $options
    ) {
        $entityType = \Magento\Bundle\Api\Data\OptionInterface::class;

        $metadata = $this->metadataPool->getMetadata($entityType);

        foreach ($options as &$option) {
            if (!isset($option[$metadata->getIdentifierField()])) {
                $option[$metadata->getIdentifierField()] = $metadata->generateIdentifier();
            } elseif (!$this->isIdentifierExists($entityType, $option[$metadata->getIdentifierField()])) {
                $this->sequenceManager->force($entityType, $option[$metadata->getIdentifierField()]);
            }
        }

        return [$options];
    }

    /**
     * Applies sequence identifiers to given selections before save.
     *
     * @param \Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver $subject
     * @param array $selections
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveSelections(
        \Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver $subject,
        array $selections
    ) {
        $entityType = \Magento\Bundle\Model\Selection::class;

        $metadata = $this->metadataPool->getMetadata($entityType);

        foreach ($selections as &$selection) {
            if (!isset($selection[$metadata->getIdentifierField()])) {
                $selection[$metadata->getIdentifierField()] = $metadata->generateIdentifier();
            } elseif (!$this->isIdentifierExists($entityType, $selection[$metadata->getIdentifierField()])) {
                $this->sequenceManager->force($entityType, $selection[$metadata->getIdentifierField()]);
            }
        }

        return [$selections];
    }

    /**
     * Checks whether given identifier exists for the corresponding entity type.
     *
     * @param string $entityType
     * @param int $identifier
     *
     * @return bool
     */
    private function isIdentifierExists($entityType, $identifier)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $sequenceInfo = $this->sequenceRegistry->retrieve($entityType);

        $connection = $this->resourceConnection->getConnectionByName(
            $metadata->getEntityConnectionName()
        );

        return (bool) $connection->fetchOne(
            $connection->select()
                ->from(
                    $this->resourceConnection->getTableName($sequenceInfo['sequenceTable']),
                    ['sequence_value']
                )
                ->where('sequence_value = ?', $identifier)
        );
    }
}
