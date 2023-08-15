<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Helper;

use Magento\CustomerCustomAttributes\Helper\Address;
use Magento\CustomerCustomAttributes\Helper\Customer;
use Magento\CustomerCustomAttributes\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var Config|MockObject
     */
    private $eavConfig;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDate;

    /**
     * @var FilterManager|MockObject
     */
    private $filterManager;

    /**
     * @var Address|MockObject
     */
    private $customerAddress;

    /**
     * @var Customer|MockObject
     */
    private $customer;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->getMockForAbstractClass();

        $this->filterManager = $this->getMockBuilder(FilterManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new Data(
            $this->context,
            $this->eavConfig,
            $this->localeDate,
            $this->filterManager,
            $this->customerAddress,
            $this->customer
        );
    }

    public function testGetAttributeValidateFilters(): void
    {
        $result = $this->helper->getAttributeValidateFilters();

        self::assertIsArray($result);
        self::assertArrayHasKey('length', $result);
        self::assertEquals(__('Length Only'), $result['length']);
    }

    /**
     * Checks attributes according to provided input types.
     *
     * @param {String} $inputType - type of input field
     * @param {Boolean} $lengthValidation - is length validation allowed
     * @dataProvider inputTypesDataProvider
     */
    public function testGetAttributeInputTypesWithInputTypes($inputType, $lengthValidation): void
    {
        $result = $this->helper->getAttributeInputTypes($inputType);

        self::assertIsArray($result);
        self::assertArrayHasKey('validate_filters', $result);
        self::assertIsArray($result['validate_filters']);
        self::assertEquals($lengthValidation, in_array('length', $result['validate_filters'], true));
    }

    /**
     * Checks attributes according to provided input types.
     *
     * @param {String} $inputType - type of input field
     * @param {Boolean} $lengthValidation - is length validation allowed
     * @dataProvider inputTypesDataProvider
     */
    public function testGetAttributeInputTypesWithInputTypeNull($inputType, $lengthValidation): void
    {
        $result = $this->helper->getAttributeInputTypes();

        self::assertIsArray($result);
        self::assertArrayHasKey($inputType, $result);
        self::assertIsArray($result[$inputType]);
        self::assertArrayHasKey('validate_filters', $result[$inputType]);
        self::assertIsArray($result[$inputType]['validate_filters']);
        self::assertEquals(
            $lengthValidation,
            in_array('length', $result[$inputType]['validate_filters'], true)
        );
    }

    /**
     * Provides possible input types
     *
     * @return array
     */
    public function inputTypesDataProvider(): array
    {
        return [
            ['text', true],
            ['textarea', false],
            ['multiline', true],
            ['date', false],
            ['select', false],
            ['multiselect', false],
            ['boolean', false],
            ['file', false],
            ['image', false]
        ];
    }
}
