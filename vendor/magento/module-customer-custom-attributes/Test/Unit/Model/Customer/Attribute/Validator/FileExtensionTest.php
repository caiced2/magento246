<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\FileExtension;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\FileExtension class.
 */
class FileExtensionTest extends TestCase
{
    /**
     * @var NotProtectedExtension|MockObject
     */
    private $extensionValidatorMock;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var FileExtension
     */
    private $fileExtensionValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->extensionValidatorMock = $this->createMock(NotProtectedExtension::class);
        $this->attributeMock = $this->getMockBuilder(AttributeInterface::class)
            ->addMethods(['getData'])
            ->getMockForAbstractClass();

        $objectHelper = new ObjectManager($this);
        $this->fileExtensionValidator = $objectHelper->getObject(
            FileExtension::class,
            [
                'extensionValidator' => $this->extensionValidatorMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testValidate(): void
    {
        $fileExtension = 'jpeg';

        $this->attributeMock
            ->method('getData')
            ->withConsecutive(['frontend_input'], ['file_extensions'])
            ->willReturnOnConsecutiveCalls('file', $fileExtension);
        $this->extensionValidatorMock->expects($this->once())
            ->method('isValid')
            ->with($fileExtension)
            ->willReturn(true);

        $this->fileExtensionValidator->validate($this->attributeMock);
    }

    /**
     * @return void
     */
    public function testValidateWithException(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Please correct the value for file extensions.');
        $fileExtension = 'php';

        $this->attributeMock
            ->method('getData')
            ->withConsecutive(['frontend_input'], ['file_extensions'])
            ->willReturnOnConsecutiveCalls('file', $fileExtension);
        $this->extensionValidatorMock->expects($this->once())
            ->method('isValid')
            ->with($fileExtension)
            ->willReturn(false);

        $this->fileExtensionValidator->validate($this->attributeMock);
    }
}
