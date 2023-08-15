<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\Option;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\FormData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\Option class.
 */
class OptionTest extends TestCase
{
    /**
     * @var FormData|MockObject
     */
    private $formDataSerializerMock;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var Option
     */
    private $optionValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->formDataSerializerMock = $this->createMock(FormData::class);
        $multipleAttributeList = ['select' => 'option'];
        $this->attributeMock = $this->getMockBuilder(AttributeInterface::class)
            ->addMethods(['getData', 'getSerializedOptions'])
            ->getMockForAbstractClass();

        $objectHelper = new ObjectManager($this);
        $this->optionValidator = $objectHelper->getObject(
            Option::class,
            [
                'formDataSerializer' => $this->formDataSerializerMock,
                'multipleAttributeList' => $multipleAttributeList
            ]
        );
    }

    /**
     * @param array $optionsData
     * @param string $errorMessage
     *
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $optionsData, string $errorMessage): void
    {
        $serializedData = '["serialized_data":1]';

        $this->formDataSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($optionsData);
        $this->attributeMock
            ->method('getSerializedOptions')
            ->willReturnOnConsecutiveCalls($serializedData, $serializedData);
        $this->attributeMock
            ->method('getData')
            ->with('frontend_input')
            ->willReturn('select');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->optionValidator->validate($this->attributeMock);
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [
                'optionsData' => [
                    'option' => [
                        'order' => [
                            'option1' => 1,
                            'option2' => 2
                        ],
                        'value' => [
                            'option1' => ['1'],
                            'option2' => ['1']
                        ],
                        'delete' => [
                            'option1' => '',
                            'option2' => ''
                        ],
                    ],
                ],
                'errorMessage' => (string) __('The value of Admin must be unique.')
            ],
            [
                'optionsData' => [
                    'option' => [
                        'order' => [
                            'option1' => 1
                        ],
                        'value' => [
                            'option1' => [
                                0 => '',
                                1 => ''
                            ],
                        ],
                        'delete' => [
                            'option1' => []
                        ]
                    ]
                ],
                'errorMessage' => (string) __('The value of Admin scope can\'t be empty.')
            ]
        ];
    }

    /**
     * @return void
     */
    public function testValidateWithSerializedException(): void
    {
        $this->attributeMock->expects($this->exactly(2))
            ->method('getSerializedOptions')
            ->willReturn('test');
        $this->formDataSerializerMock->expects($this->once())
            ->method('unserialize')
            ->willThrowException(new \InvalidArgumentException());

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The attribute couldn\'t be validated due to an error. ' .
            'Verify your information and try again. If the error persists, please try again later.');

        $this->optionValidator->validate($this->attributeMock);
    }
}
