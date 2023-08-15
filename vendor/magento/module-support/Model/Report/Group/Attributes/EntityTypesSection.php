<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Support\Model\Report\Group\AbstractSection;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory as EntityTypeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Type\Collection as EntityTypeCollection;

/**
 * Entity Types section of Attributes report group
 */
class EntityTypesSection extends AbstractSection
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory
     */
    protected $entityTypeCollectionFactory;

    /**
     * @var \Magento\Support\Model\Report\Group\Attributes\DataFormatter
     */
    protected $dataFormatter;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $entityTypeCollectionFactory
     * @param \Magento\Support\Model\Report\Group\Attributes\DataFormatter $dataFormatter
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        EntityTypeCollectionFactory $entityTypeCollectionFactory,
        DataFormatter $dataFormatter,
        array $data = []
    ) {
        $this->entityTypeCollectionFactory = $entityTypeCollectionFactory;
        $this->dataFormatter = $dataFormatter;
        parent::__construct($logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        /** @var \Magento\Eav\Model\ResourceModel\Entity\Type\Collection $entityTypeCollection */
        $entityTypeCollection = $this->entityTypeCollectionFactory->create();
        $entityTypeCollection->load();
        return [
            (string)__('Entity Types') => [
                'headers' => [
                    __('ID'), __('Code'), __('Model'), __('Attribute Model'),
                    __('Increment Model'), __('Main Table'), __('Additional Attribute Table')
                ],
                'data' => $this->extractEntityTypeCollectionData($entityTypeCollection)
            ]
        ];
    }

    /**
     * Extract data from collection of Eav entity types
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\Collection $entityTypeCollection
     * @return array
     */
    protected function extractEntityTypeCollectionData(
        EntityTypeCollection $entityTypeCollection
    ) {
        $data = [];
        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        foreach ($entityTypeCollection as $entityType) {
            $data[] = [
                $entityType->getEntityTypeId(),
                $entityType->getEntityTypeCode(),
                $this->dataFormatter->prepareModelValue($entityType->getEntityModel()),
                $this->dataFormatter->prepareModelValue($entityType->getAttributeModel()),
                $this->dataFormatter->prepareModelValue($entityType->getIncrementModel()),
                $entityType->getEntityTable(),
                $entityType->getAdditionalAttributeTable()
            ];
        }
        return $data;
    }
}
