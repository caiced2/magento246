<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Service;

use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataConverter;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Model\Service\RmaAttributesManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RmaAttributesManagementTest extends TestCase
{
    private const ENTITY_TYPE_ID = 101;

    private const ENTITY_ATTR_SET_ID = 201;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AttributeMetadataDataProvider|MockObject
     */
    protected $metadataDataProviderMock;

    /**
     * @var AttributeMetadataConverter|MockObject
     */
    protected $metadataConverterMock;

    /**
     * @var RmaAttributesManagement
     */
    protected $rmaAttributesManagement;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->metadataDataProviderMock = $this->createMock(
            AttributeMetadataDataProvider::class
        );
        $this->metadataConverterMock = $this->createMock(AttributeMetadataConverter::class);
        $entityTypeModel = $this->createConfiguredMock(
            Type::class,
            [
                'getEntityTypeId' => self::ENTITY_TYPE_ID,
                'getDefaultAttributeSetId' => self::ENTITY_ATTR_SET_ID,
                'getEntityTypeCode' => RmaAttributesManagement::ENTITY_TYPE,
            ]
        );
        $this->eavConfig = $this->createMock(Config::class);
        $this->eavConfig->method('getEntityType')
            ->willReturnMap(
                [
                    [RmaAttributesManagement::ENTITY_TYPE, $entityTypeModel]
                ]
            );
        $this->rmaAttributesManagement = $this->objectManager->getObject(
            RmaAttributesManagement::class,
            [
                'metadataDataProvider' => $this->metadataDataProviderMock,
                'metadataConverter' => $this->metadataConverterMock,
                'eavConfig' => $this->eavConfig,
            ]
        );
    }

    /**
     * Run test getAttributes method
     *
     * @return void
     */
    public function testGetAttributes()
    {
        $expectedAttributes = ['attribute-code' => 'metadata'];
        $attributeMock = $this->createMock(Attribute::class);

        $this->metadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->willReturn([$attributeMock]);
        $attributeMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('attribute-code');
        $this->metadataConverterMock->expects($this->once())
            ->method('createMetadataAttribute')
            ->with($attributeMock)
            ->willReturn('metadata');

        $this->assertEquals($expectedAttributes, $this->rmaAttributesManagement->getAttributes('form-code'));
    }

    /**
     * Run test getAttributeMetadata method
     *
     * @return void
     */
    public function testGetAttributeMetadata()
    {
        $expectedAttributeMetadata = 'result-metadata';
        $attributeMock = $this->getMockForAbstractClass(
            AbstractAttribute::class,
            [],
            '',
            false,
            true,
            true,
            ['getIsVisible']
        );
        $this->metadataDataProviderMock->expects($this->once())
            ->method('getAttribute')
            ->willReturn($attributeMock);
        $attributeMock->expects($this->atLeastOnce())
            ->method('getIsVisible')
            ->willReturn(1);
        $this->metadataConverterMock->expects($this->once())
            ->method('createMetadataAttribute')
            ->with($attributeMock)
            ->willReturn($expectedAttributeMetadata);

        $this->assertEquals($expectedAttributeMetadata, $this->rmaAttributesManagement->getAttributeMetadata('code'));
    }

    /**
     * Run test getAttributeMetadata method [Exception]
     *
     * @return void
     */
    public function testGetAttributeMetadataException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->metadataDataProviderMock->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);

        $this->rmaAttributesManagement->getAttributeMetadata('code');
    }

    /**
     * Run test getAllAttributesMetadata method
     *
     * @return void
     */
    public function testGetAllAttributesMetadata()
    {
        $attributeCodes = ['test-code'];
        $attributeMock = $this->getMockForAbstractClass(
            AbstractAttribute::class,
            [],
            '',
            false,
            true,
            true,
            ['getIsVisible']
        );

        $this->metadataDataProviderMock->expects($this->once())
            ->method('getAllAttributeCodes')
            ->with(RmaAttributesManagement::ENTITY_TYPE, self::ENTITY_ATTR_SET_ID)
            ->willReturn($attributeCodes);
        $this->metadataDataProviderMock->expects($this->once())
            ->method('getAttribute')
            ->willReturn($attributeMock);
        $attributeMock->expects($this->atLeastOnce())
            ->method('getIsVisible')
            ->willReturn(1);
        $this->metadataConverterMock->expects($this->once())
            ->method('createMetadataAttribute')
            ->with($attributeMock)
            ->willReturn('test-code');

        $this->assertEquals($attributeCodes, $this->rmaAttributesManagement->getAllAttributesMetadata());
    }

    /**
     * Run test getCustomAttributesMetadata method
     *
     * @return void
     */
    public function testGetCustomAttributesMetadata()
    {
        $attributeMetadataMock = $this->getMockForAbstractClass(
            AttributeMetadataInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $attributeMock = $this->getMockForAbstractClass(
            AbstractAttribute::class,
            [],
            '',
            false,
            true,
            true,
            ['getIsVisible']
        );

        $attributeCodes = [$attributeMetadataMock];
        $this->metadataDataProviderMock->expects($this->once())
            ->method('getAllAttributeCodes')
            ->with(RmaAttributesManagement::ENTITY_TYPE, self::ENTITY_ATTR_SET_ID)
            ->willReturn($attributeCodes);
        $this->metadataDataProviderMock->expects($this->once())
            ->method('getAttribute')
            ->willReturn($attributeMock);
        $attributeMock->expects($this->atLeastOnce())
            ->method('getIsVisible')
            ->willReturn(1);
        $this->metadataConverterMock->expects($this->once())
            ->method('createMetadataAttribute')
            ->with($attributeMock)
            ->willReturn($attributeMetadataMock);
        $attributeMetadataMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('get_custom_attributes');

        $this->assertEquals(
            [
                $attributeMetadataMock,
            ],
            $this->rmaAttributesManagement->getCustomAttributesMetadata()
        );
    }
}
