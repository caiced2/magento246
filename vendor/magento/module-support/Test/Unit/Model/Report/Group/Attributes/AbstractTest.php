<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Attributes;

use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Attributes\AbstractAttributesSection;
use Magento\Support\Model\Report\Group\Attributes\DataFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    /**
     * @var AbstractAttributesSection
     */
    protected $section;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory|MockObject
     */
    protected $entityTypeFactoryMock;

    /**
     * @var MockObject
     */
    protected $attributeCollectionFactoryMock;

    /**
     * @var DataFormatter|MockObject
     */
    protected $dataFormatterMock;

    /**
     * @var Json|MockObject
     */
    protected $serializer;

    /**
     * @param string $sectionClass
     * @param array $sectionData
     */
    protected function prepareObjects($sectionClass, $sectionData = [])
    {
        $this->entityTypeFactoryMock = $this->getMockBuilder(\Magento\Eav\Model\Entity\TypeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->attributeCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dataFormatterMock = $this->getMockBuilder(
            DataFormatter::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->serializer = $this->getMockBuilder(Json::class)
            ->getMock();

        $this->section = $this->objectManagerHelper->getObject(
            $sectionClass,
            [
                'entityTypeFactory' => $this->entityTypeFactoryMock,
                'attributeCollectionFactory' => $this->attributeCollectionFactoryMock,
                'dataFormatter' => $this->dataFormatterMock,
                'data' => $sectionData,
                'serializer' => $this->serializer
            ]
        );
    }

    /**
     * Create entity type mock object
     *
     * @param array $data
     * @return Type|MockObject
     */
    protected function createEntityTypeMock(array $data)
    {
        $data = array_merge(array_fill_keys(['id', 'entity_type_code'], null), $data);
        $entityTypeMock = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityTypeMock->expects($this->any())
            ->method('getId')
            ->willReturn($data['id']);
        $entityTypeMock->expects($this->any())
            ->method('getEntityTypeCode')
            ->willReturn($data['entity_type_code']);

        return $entityTypeMock;
    }

    /**
     * Create entity attribute collection mock object
     *
     * @param array $data
     * @return Collection|MockObject
     */
    protected function createEntityAttributeCollectionMock(array $data)
    {
        $entityAttributeCollectionMock = $this->createMock(
            Collection::class
        );

        $entityAttributeCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($data));

        return $entityAttributeCollectionMock;
    }

    /**
     * Create entity attribute mock object
     *
     * @param array $data
     * @return Attribute|MockObject
     */
    protected function createEntityAttributeMock(array $data)
    {
        $data = array_merge(
            array_fill_keys(
                [
                    'attribute_id', 'attribute_code', 'is_user_defined', 'source_model',
                    'backend_model', 'frontend_model', 'frontend_input', 'backend_type',
                    'entity_type'
                ],
                null
            ),
            $data
        );
        $entityAttributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityAttributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($data['attribute_code']);
        $entityAttributeMock->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn($data['frontend_input']);
        $entityAttributeMock->expects($this->any())
            ->method('getBackendType')
            ->willReturn($data['backend_type']);
        $entityAttributeMock->expects($this->any())
            ->method('getIsUserDefined')
            ->willReturn($data['is_user_defined']);
        $entityAttributeMock->expects($this->any())
            ->method('getEntityType')
            ->willReturn($data['entity_type']);
        $entityAttributeMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['attribute_id', null, $data['attribute_id']],
                    ['source_model', null, $data['source_model']],
                    ['backend_model', null, $data['backend_model']],
                    ['frontend_model', null, $data['frontend_model']]
                ]
            );

        return $entityAttributeMock;
    }
}
