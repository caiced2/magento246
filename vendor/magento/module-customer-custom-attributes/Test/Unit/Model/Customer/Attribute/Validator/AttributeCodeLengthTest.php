<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeCodeLength;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Validator\StringLength;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeCodeLength class.
 */
class AttributeCodeLengthTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var Type|MockObject
     */
    private $entityTypeMock;

    /**
     * @var StringLength|MockObject
     */
    private $stringLengthMock;

    /**
     * @var AttributeCodeLength
     */
    private $attributeCodeLengthValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->attributeMock = $this->getMockBuilder(AttributeInterface::class)
            ->addMethods(['getId', 'getAttributeCode', 'getEntityType'])
            ->getMockForAbstractClass();
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->stringLengthMock = $this->createMock(StringLength::class);

        $this->attributeCodeLengthValidator = $this->objectManager->getObject(
            AttributeCodeLength::class,
            [
                'stringLength' => $this->stringLengthMock,
                'codeLengthByEntityType' => [
                    'customer' => 51,
                    'customer_address' => 60,
                ],
            ]
        );
    }

    /**
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @param int $maxLength
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $attributeCode, string $entityTypeCode, int $maxLength)
    {
        $this->prepareValidation($attributeCode, $entityTypeCode, $maxLength);
        $this->stringLengthMock->expects($this->atLeastOnce())
            ->method('isValid')
            ->with($attributeCode)
            ->willReturn(true);

        $this->attributeCodeLengthValidator->validate($this->attributeMock);
    }

    /**
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @param int $maxLength
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidateWithException(string $attributeCode, string $entityTypeCode, int $maxLength)
    {
        $this->prepareValidation($attributeCode, $entityTypeCode, $maxLength);
        $this->stringLengthMock->expects($this->atLeastOnce())
            ->method('isValid')
            ->with($attributeCode)
            ->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'The attribute code needs to be ' . $maxLength . ' characters or fewer. Re-enter the code and try again.'
        );

        $this->attributeCodeLengthValidator->validate($this->attributeMock);
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            ['test_attribute_code', 'customer', 51],
            ['test_attribute_long_code', 'customer_address', 60],
        ];
    }

    /**
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @param int $maxLength
     * @return void
     */
    private function prepareValidation(string $attributeCode, string $entityTypeCode, int $maxLength): void
    {
        $this->attributeMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getEntityType')
            ->willReturn($this->entityTypeMock);
        $this->entityTypeMock->expects($this->atLeastOnce())
            ->method('getEntityTypeCode')
            ->willReturn($entityTypeCode);
        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);
        $this->stringLengthMock->expects($this->atLeastOnce())
            ->method('setMax')
            ->with($maxLength)
            ->willReturnSelf();
    }
}
