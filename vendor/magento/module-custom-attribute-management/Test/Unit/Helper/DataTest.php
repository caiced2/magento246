<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomAttributeManagement\Test\Unit\Helper;

use Magento\CustomAttributeManagement\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filterManagerMock = $this->getMockBuilder(FilterManager::class)
            ->addMethods(['stripTags'])
            ->disableOriginalConstructor()
            ->getMock();

        $filterManagerMock->expects($this->any())
            ->method('stripTags')
            ->willReturn('stripTags');
        $this->localeDate = $this->getMockForAbstractClass(TimezoneInterface::class);
        $this->_helper = new Data(
            $contextMock,
            $this->createMock(Config::class),
            $this->localeDate,
            $filterManagerMock
        );
    }

    /**
     * @param string $frontendInput
     * @param array $validateRules
     * @param array $result
     * @dataProvider checkValidateRulesDataProvider
     */
    public function testCheckValidateRules($frontendInput, $validateRules, $result)
    {
        $this->assertEquals($result, $this->_helper->checkValidateRules($frontendInput, $validateRules));
    }

    /**
     * @return array
     */
    public function checkValidateRulesDataProvider()
    {
        return [
            [
                'text',
                ['min_text_length' => 1, 'max_text_length' => 2],
                [],
            ],
            [
                'text',
                ['min_text_length' => 3, 'max_text_length' => 2],
                [__('Please correct the values for minimum and maximum text length validation rules.')]
            ],
            [
                'textarea',
                ['min_text_length' => 1, 'max_text_length' => 2],
                []
            ],
            [
                'textarea',
                ['min_text_length' => 3, 'max_text_length' => 2],
                [__('Please correct the values for minimum and maximum text length validation rules.')]
            ],
            [
                'multiline',
                ['min_text_length' => 1, 'max_text_length' => 2],
                []
            ],
            [
                'multiline',
                ['min_text_length' => 3, 'max_text_length' => 2],
                [__('Please correct the values for minimum and maximum text length validation rules.')]
            ],
            [
                'date',
                ['date_range_min' => '1', 'date_range_max' => '2'],
                []
            ],
            [
                'date',
                ['date_range_min' => '3', 'date_range_max' => '2'],
                [__('Please correct the values for minimum and maximum date validation rules.')]
            ],
            [
                'empty',
                ['date_range_min' => '3', 'date_range_max' => '2'],
                []
            ]
        ];
    }

    public function testGetAttributeInputTypes()
    {
        $inputTypes = [
            'text' => [
                'label' => __('Text Field'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => ['alphanumeric', 'alphanum-with-spaces', 'numeric', 'alpha', 'url', 'email'],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'varchar',
                'default_value' => 'text',
            ],
            'textarea' => [
                'label' => __('Text Area'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => [],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'text',
                'default_value' => 'textarea',
            ],
            'multiline' => [
                'label' => __('Multiple Line'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => ['alphanumeric', 'alphanum-with-spaces', 'numeric', 'alpha', 'url', 'email'],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'text',
                'default_value' => 'text',
            ],
            'date' => [
                'label' => __('Date'),
                'manage_options' => false,
                'validate_types' => ['date_range_min', 'date_range_max'],
                'validate_filters' => ['date'],
                'filter_types' => ['date'],
                'backend_model' => Datetime::class,
                'backend_type' => 'datetime',
                'default_value' => 'date',
            ],
            'select' => [
                'label' => __('Dropdown'),
                'manage_options' => true,
                'option_default' => 'radio',
                'validate_types' => [],
                'validate_filters' => [],
                'filter_types' => [],
                'source_model' => Table::class,
                'backend_type' => 'int',
                'default_value' => false,
            ],
            'multiselect' => [
                'label' => __('Multiple Select'),
                'manage_options' => true,
                'option_default' => 'checkbox',
                'validate_types' => [],
                'filter_types' => [],
                'validate_filters' => [],
                'backend_model' => ArrayBackend::class,
                'source_model' => Table::class,
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
            'boolean' => [
                'label' => __('Yes/No'),
                'manage_options' => false,
                'validate_types' => [],
                'validate_filters' => [],
                'filter_types' => [],
                'source_model' => Boolean::class,
                'backend_type' => 'int',
                'default_value' => 'yesno',
            ],
            'file' => [
                'label' => __('File (attachment)'),
                'manage_options' => false,
                'validate_types' => ['max_file_size', 'file_extensions'],
                'validate_filters' => [],
                'filter_types' => [],
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
            'image' => [
                'label' => __('Image File'),
                'manage_options' => false,
                'validate_types' => ['max_file_size', 'max_image_width', 'max_image_heght'],
                'validate_filters' => [],
                'filter_types' => [],
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
        ];

        $this->assertEquals($inputTypes, $this->_helper->getAttributeInputTypes());
        foreach ($inputTypes as $key => $value) {
            $this->assertEquals($value, $this->_helper->getAttributeInputTypes($key));
        }
        $this->assertEquals([], $this->_helper->getAttributeInputTypes('empty'));
    }

    public function testGetFrontendInputOptions()
    {
        $result = [
            [
                'value' => 'text',
                'label' => __('Text Field'),
            ],
            [
                'value' => 'textarea',
                'label' => __('Text Area'),
            ],
            [
                'value' => 'multiline',
                'label' => __('Multiple Line'),
            ],
            [
                'value' => 'date',
                'label' => __('Date'),
            ],
            [
                'value' => 'select',
                'label' => __('Dropdown'),
            ],
            [
                'value' => 'multiselect',
                'label' => __('Multiple Select'),
            ],
            [
                'value' => 'boolean',
                'label' => __('Yes/No'),
            ],
            [
                'value' => 'file',
                'label' => __('File (attachment)'),
            ],
            [
                'value' => 'image',
                'label' => __('Image File'),
            ],
        ];

        $this->assertEquals($result, $this->_helper->getFrontendInputOptions());
    }

    public function testGetAttributeValidateFilters()
    {
        $result = [
            'alphanumeric' => __('Alphanumeric'),
            'alphanum-with-spaces' => __('Alphanumeric with spaces'),
            'numeric' => __('Numeric Only'),
            'alpha' => __('Alpha Only'),
            'url' => __('URL'),
            'email' => __('Email'),
            'date' => __('Date'),
        ];
        $this->assertEquals($result, $this->_helper->getAttributeValidateFilters());
    }

    public function testGetAttributeFilterTypes()
    {
        $result = [
            'striptags' => __('Strip HTML Tags'),
            'escapehtml' => __('Escape HTML Entities'),
            'date' => __('Normalize Date'),
        ];
        $this->assertEquals($result, $this->_helper->getAttributeFilterTypes());
    }

    public function testGetAttributeElementScopes()
    {
        $result = [
            'is_required' => 'website',
            'is_visible' => 'website',
            'multiline_count' => 'website',
            'default_value_text' => 'website',
            'default_value_yesno' => 'website',
            'default_value_date' => 'website',
            'default_value_textarea' => 'website',
            'date_range_min' => 'website',
            'date_range_max' => 'website',
        ];
        $this->assertEquals($result, $this->_helper->getAttributeElementScopes());
    }

    /**
     * @test
     * @param string $inputType
     * @param string|false $result
     * @dataProvider getAttributeDefaultValueByInputDataProvider
     */
    public function testGetAttributeDefaultValueByInput($inputType, $result)
    {
        $this->assertEquals($result, $this->_helper->getAttributeDefaultValueByInput($inputType));
    }

    /**
     * @return array
     */
    public function getAttributeDefaultValueByInputDataProvider()
    {
        return [
            [
                'text',
                'default_value_text',
            ],
            [
                'textarea',
                'default_value_textarea',
            ],
            [
                'multiline',
                'default_value_text',
            ],
            [
                'date',
                'default_value_date',
            ],
            [
                'select',
                false,
            ],
            [
                'multiselect',
                false,
            ],
            [
                'boolean',
                'default_value_yesno',
            ],
            [
                'file',
                false,
            ],
            [
                'image',
                false,
            ],
            [
                'empty',
                false,
            ]
        ];
    }

    /**
     * @test
     * @param string $inputType
     * @param array $data
     * @param array $result
     * @param int $allowedInvocations
     * @dataProvider getAttributeValidateRulesDataProvider
     */
    public function testGetAttributeValidateRules($inputType, $data, $result, $allowedInvocations)
    {
        $this->localeDate->expects($this->atMost($allowedInvocations))
            ->method('date')
            ->willReturn((new \DateTime('01/01/2014')));
        $this->assertEquals($result, $this->_helper->getAttributeValidateRules($inputType, $data));
    }

    /**
     * @return array
     */
    public function getAttributeValidateRulesDataProvider()
    {
        return [
            [
                'text',
                ['min_text_length' => 1, 'max_text_length' => 2, 'input_validation' => 'numeric'],
                ['min_text_length' => 1, 'max_text_length' => 2, 'input_validation' => 'numeric'],
                0
            ],
            [
                'text',
                ['min_text_length' => 1, 'max_text_length' => 2, 'input_validation' => 'test'],
                ['min_text_length' => 1, 'max_text_length' => 2],
                0
            ],
            [
                'text',
                ['min_text_length' => 1],
                ['min_text_length' => 1],
                0
            ],
            [
                'date',
                ['date_range_max' => '01/01/2014'],
                ['date_range_max' => 1388563200],
                2
            ]
        ];
    }

    public function testGetAttributeBackendModelByInputType()
    {
        $this->assertNull($this->_helper->getAttributeBackendModelByInputType('empty'));
        $this->assertEquals(
            Datetime::class,
            $this->_helper->getAttributeBackendModelByInputType('date')
        );
    }

    public function testGetAttributeSourceModelByInputType()
    {
        $this->assertNull($this->_helper->getAttributeSourceModelByInputType('empty'));
        $this->assertEquals(
            Table::class,
            $this->_helper->getAttributeSourceModelByInputType('multiselect')
        );
    }

    public function testGetAttributeBackendTypeByInputType()
    {
        $this->assertNull($this->_helper->getAttributeBackendTypeByInputType('empty'));
        $this->assertEquals('varchar', $this->_helper->getAttributeBackendTypeByInputType('text'));
    }

    public function testGetUserDefinedAttributeCodes()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Use helper with defined EAV entity.');
        $this->_helper->getUserDefinedAttributeCodes();
    }

    /**
     * @param array $data
     * @param array $result
     * @throws LocalizedException
     * @dataProvider testFilterPostDataDataProvider
     */
    public function testFilterPostData($data, $result)
    {
        if (!empty($data['is_required']) || !empty($data['is_visible'])) {
            $this->expectException(LocalizedException::class);
        }
        $this->assertEquals($result, $this->_helper->filterPostData($data));
    }

    /**
     * @return array
     */
    public function testFilterPostDataDataProvider()
    {
        return [
            [
                'data' => [
                    'frontend_label' => ['Label'],
                    'attribute_code' => 'code',
                ],
                'result' => [
                    'frontend_label' => ['stripTags'],
                    'attribute_code' => 'code'
                ]
            ],
            [
                'data' => [
                    'frontend_label' => ['Label'],
                    'attribute_code' => 'code',
                    'is_required' => '1',
                    'is_visible' => '1'
                ],
                'result' => [
                    'frontend_label' => ['stripTags'],
                    'attribute_code' => 'code',
                    'is_required' => '1',
                    'is_visible' => '1'
                ]
            ],
        ];
    }

    public function testFilterPostDataWithException()
    {
        $exceptionMessage = 'The attribute code is invalid.';
        $exceptionMessage .= ' Please use only letters (a-z), numbers (0-9) or underscores (_) in this field.';
        $exceptionMessage .= ' The first character should be a letter.';
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $data = ['frontend_label' => ['Label'], 'attribute_code' => 'Code'];
        $this->_helper->filterPostData($data);
    }

    public function testFilterPostDataWithoutInputValidationFilter()
    {
        $data = [
            'frontend_label' => ['Label'],
            'input_validation' => '',
            'min_text_length' => 4,
            'max_text_length' => 7
        ];
        $result = ['frontend_label' => ['stripTags'],'input_validation' => ''];
        $this->assertEquals($result, $this->_helper->filterPostData($data));
    }

    public function testGetAttributeFormOptions()
    {
        $this->assertEquals(
            [['label' => __('Default EAV Form'), 'value' => 'default']],
            $this->_helper->getAttributeFormOptions()
        );
    }

    /**
     * @param string $extension
     * @param bool $isValid
     *
     * @dataProvider validateFileExtensionsProvider
     */
    public function testValidateFileExtensions(bool $isFlagSet, string $extension): void
    {
        $isValidExtension = true;
        try {
            $this->_helper->validateFileExtensions($extension);
        } catch (\Throwable $exception) {
            $isValidExtension = false;
        }
        $this->assertSame($isValidExtension, $isFlagSet);
    }

    /**
     * @return array
     */
    public function validateFileExtensionsProvider(): array
    {
        return [
            [
                true,
                'jpeg'
            ],
            [
                true,
                '4324324324jpeg'
            ],
            [
                true,
                'jpeg,4324324324jpeg'
            ],
            [
                false,
                '$#@$#@$3'
            ],
            [
                false,
                '../../jpeg'
            ],
            [
                false,
                '$#@$#@$3,php$,../../jpeg,phar.$'
            ],
        ];
    }
}
