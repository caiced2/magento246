<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeDuplication;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeDuplication class.
 */
class AttributeDuplicationTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $eavConfigMock;

    /**
     * @var AttributeFactory|MockObject
     */
    private $attributeFactoryMock;

    /**
     * @var WebsiteFactory|MockObject
     */
    private $websiteFactory;

    /**
     * @var Type|MockObject
     */
    private $entityTypeMock;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var AttributeDuplication
     */
    private $attributeDuplicationValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->websiteFactory = $this->createMock(WebsiteFactory::class);
        $this->attributeFactoryMock = $this->createMock(AttributeFactory::class);
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->attributeMock = $this->createMock(Attribute::class);

        $objectHelper = new ObjectManager($this);
        $this->attributeDuplicationValidator = $objectHelper->getObject(
            AttributeDuplication::class,
            [
                'eavConfig' => $this->eavConfigMock,
                'attributeFactory' => $this->attributeFactoryMock,
                'websiteFactory' => $this->websiteFactory,
            ]
        );
    }

    /**
     * @return void
     */
    public function testValidate()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('An attribute with this code already exists.');
        $websiteMock = $this->createMock(Website::class);
        $newAttribute = $this->getMockBuilder(AttributeInterface::class)
            ->addMethods(['getId', 'getAttributeCode', 'getWebsite', 'getEntityTypeId'])
            ->getMockForAbstractClass();
        $attributeCode = 'test_attribute';
        $entityTypeId = 1;

        $newAttribute->expects($this->once())->method('getId')->willReturn(null);
        $newAttribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $newAttribute->expects($this->once())->method('getWebsite')->willReturn($websiteMock);
        $newAttribute->expects($this->once())->method('getEntityTypeId')->willReturn($entityTypeId);
        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with($entityTypeId)
            ->willReturn($this->entityTypeMock);
        $this->attributeFactoryMock->expects($this->once())->method('create')->willReturn($this->attributeMock);
        $this->attributeMock->expects($this->once())->method('setWebsite')->with($websiteMock)->willReturnSelf();
        $this->attributeMock->expects($this->once())
            ->method('loadByCode')
            ->with($this->entityTypeMock, $attributeCode)
            ->willReturn($this->attributeMock);
        $this->attributeMock->expects($this->once())->method('getId')->willReturn(1);

        $this->attributeDuplicationValidator->validate($newAttribute);
    }
}
