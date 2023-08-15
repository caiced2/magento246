<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\TypeFactory as EntityTypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Type as EntityTypeResource;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\ObjectManager;

/**
 * New Eav Attributes section of Attributes report group
 */
class NewEavAttributesSection extends AbstractAttributesSection
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * NewEavAttributesSection constructor.
     *
     * @param LoggerInterface $logger
     * @param EntityTypeFactory $entityTypeFactory
     * @param EntityTypeResource $entityTypeResource
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param DataFormatter $dataFormatter
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        LoggerInterface $logger,
        EntityTypeFactory $entityTypeFactory,
        EntityTypeResource $entityTypeResource,
        AttributeCollectionFactory $attributeCollectionFactory,
        DataFormatter $dataFormatter,
        array $data = [],
        Json $serializer = null
    ) {
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        parent::__construct(
            $logger,
            $entityTypeFactory,
            $entityTypeResource,
            $attributeCollectionFactory,
            $dataFormatter,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $attributeCollection = $this->getAttributesCollection();
        return [
            (string)__('New Eav Attributes') => $this->generateSectionData(
                $attributeCollection,
                ['entity_type_code']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function extractAttributeCollectionData(
        AttributeCollection $attributeCollection,
        array $excludedFields = []
    ) {
        $data = [];
        $existedAttributes = $this->serializer->unserialize($this->data['existedAttributes']);
        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $existedAttributes)) {
                $data[] = $this->extractAttributeData($attribute, $excludedFields);
            }
        }
        return $data;
    }
}
