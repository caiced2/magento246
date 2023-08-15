<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Plugin;

use Magento\Customer\Model\Attribute;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\CustomerCustomAttributes\Plugin\ProcessCustomerCustomBooleanAttributeOptions;
use Magento\Ui\Component\Form\AttributeMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ProcessCustomerCustomBooleanAttributeOptionsTest
 *
 * Test process customer custom boolean attribute options and change it
 * to boolean values
 */
class ProcessCustomerCustomBooleanAttributeOptionsTest extends TestCase
{
    /**
     * @var ProcessCustomerCustomBooleanAttributeOptions
     */
    private $model;

    /**
     * @var AttributeMapper| MockObject
     */
    private $attributeMapperMock;

    /**
     * @var AttributeInterface| MockObject
     */
    private $attributeMock;

    /**
     * Prepare testable object
     */
    protected function setUp(): void
    {
        $this->attributeMapperMock = $this->getMockBuilder(
            AttributeMapper::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->attributeMock = $this->getMockBuilder(
            Attribute::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new ProcessCustomerCustomBooleanAttributeOptions();
    }

    /**
     * Test after map custom boolean attributes plugin.
     *
     * @dataProvider dataProviderForBooleanAttribute
     * @param array $meta
     * @param array $expectedResult
     * @param int $isUserDefined
     * @param string $frontendInput
     */
    public function testAfterMap(
        array $meta,
        array $expectedResult,
        int $isUserDefined,
        string $frontendInput
    ): void {
        $this->attributeMock
            ->expects($this->any())
            ->method('getIsUserDefined')
            ->willReturn($isUserDefined);
        $this->attributeMock
            ->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn($frontendInput);
        $actualResult = $this->model->afterMap(
            $this->attributeMapperMock,
            $meta,
            $this->attributeMock
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Data provider for boolean attribute
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderForBooleanAttribute(): array
    {
        return [
            'validate meta with non-boolean attribute' => [
                'meta' => [
                    'dataType' => 'text',
                    'formElement' => 'textbox',
                    'visible' => '1',
                    'required' => '0',
                    'label' => 'Text Attribute',
                    'sortOrder' => '999',
                    'notice' => null,
                    'default' => null,
                    'size' => '1',
                    'validation' =>[]
                ],
                'expectedResult' => [
                    'dataType' => 'text',
                    'formElement' => 'textbox',
                    'visible' => '1',
                    'required' => '0',
                    'label' => 'Text Attribute',
                    'sortOrder' => '999',
                    'notice' => null,
                    'default' => null,
                    'size' => '1',
                    'validation' =>[]
                ],
                'isUserDefined' => 1,
                'frontendInput' => 'textbox'
            ],
            'validate meta with non-user defined attribute' => [
                'meta' => [
                    'dataType' => 'boolean',
                    'formElement' => 'checkbox',
                    'visible' => '1',
                    'required' => '0',
                    'label' => 'Boolean Attribute',
                    'sortOrder' => '999',
                    'notice' => null,
                    'default' => null,
                    'size' => '1',
                    'options' => [
                        [
                            'label' => 'Yes',
                            'value' => 1
                        ],
                        [
                            'label' => 'No',
                            'value' => 0,
                        ]
                    ],
                    'validation' =>[]
                ],
                'expectedResult' => [
                    'dataType' => 'boolean',
                    'formElement' => 'checkbox',
                    'visible' => '1',
                    'required' => '0',
                    'label' => 'Boolean Attribute',
                    'sortOrder' => '999',
                    'notice' => null,
                    'default' => null,
                    'size' => '1',
                    'options' => [
                        [
                            'label' => 'Yes',
                            'value' => true
                        ],
                        [
                            'label' => 'No',
                            'value' => false,
                        ]
                    ],
                    'validation' =>[]
                ],
                'isUserDefined' => 0,
                'frontendInput' => 'checkbox'
            ],
            'validate meta with user defined boolean attribute' => [
                'meta' => [
                    'dataType' => 'boolean',
                    'formElement' => 'checkbox',
                    'visible' => '1',
                    'required' => '0',
                    'label' => 'Boolean Attribute',
                    'sortOrder' => '999',
                    'notice' => null,
                    'default' => null,
                    'size' => '1',
                    'options' => [
                        [
                            'label' => 'Yes',
                            'value' => 1
                        ],
                        [
                            'label' => 'No',
                            'value' => 0,
                        ]
                    ],
                    'validation' =>[]
                ],
                'expectedResult' => [
                    'dataType' => 'boolean',
                    'formElement' => 'checkbox',
                    'visible' => '1',
                    'required' => '0',
                    'label' => 'Boolean Attribute',
                    'sortOrder' => '999',
                    'notice' => null,
                    'default' => null,
                    'size' => '1',
                    'options' => [
                        [
                            'label' => 'Yes',
                            'value' => true
                        ],
                        [
                            'label' => 'No',
                            'value' => false,
                        ]
                    ],
                    'validation' =>[]
                ],
                'isUserDefined' => 1,
                'frontendInput' => 'boolean'
            ]
        ];
    }
}
