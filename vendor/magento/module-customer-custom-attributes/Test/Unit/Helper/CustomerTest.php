<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Helper;

use Magento\CustomerCustomAttributes\Helper\Customer;
use Magento\CustomerCustomAttributes\Helper\Data;
use Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\Validator;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $_contextMock;

    /**
     * @var MockObject
     */
    protected $_dataHelperMock;

    /**
     * @var MockObject
     */
    protected $_inputValidatorMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->_contextMock = $this->getMockBuilder(
            Context::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->_dataHelperMock = $this->getMockBuilder(
            Data::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->_dataHelperMock->expects(
            $this->any()
        )->method(
            'getAttributeInputTypes'
        )->willReturn(
            []
        );

        $this->_inputValidatorMock = $this->getMockBuilder(
            Validator::class
        )->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     * @param array $data
     * @param bool $validatorResult
     * @dataProvider getFilterExceptionDataProvider
     */
    public function filterPostDataExceptionTest($data, $validatorResult)
    {
        $this->_inputValidatorMock->expects(
            $this->any()
        )->method(
            'isValid'
        )->willReturn(
            $validatorResult
        );

        $this->_inputValidatorMock->expects(
            $this->any()
        )->method(
            'getMessages'
        )->willReturn(
            ['Some error message']
        );

        $helper = new Customer(
            $this->_contextMock,
            $this->createMock(Config::class),
            $this->getMockForAbstractClass(TimezoneInterface::class),
            $this->createMock(FilterManager::class),
            $this->_dataHelperMock,
            $this->_inputValidatorMock
        );

        $this->expectException(LocalizedException::class);
        $helper->filterPostData($data);
    }

    /**
     *
     * @param array $data
     * @param array $expectedResultData
     * @dataProvider getFilterDataProvider
     * @test
     */
    public function filterPostDataTest($data, $expectedResultData)
    {
        $this->_inputValidatorMock->expects($this->once())->method('isValid')->willReturn(true);
        $this->_inputValidatorMock->expects($this->never())->method('getMessages');

        $helper = new Customer(
            $this->_contextMock,
            $this->createMock(Config::class),
            $this->getMockForAbstractClass(TimezoneInterface::class),
            $this->createMock(FilterManager::class),
            $this->_dataHelperMock,
            $this->_inputValidatorMock
        );

        $dataResult = $helper->filterPostData($data);

        $this->assertEquals($dataResult, $expectedResultData);
    }

    /**
     * Test exception data provider
     *
     * @return array
     */
    public function getFilterExceptionDataProvider()
    {
        return [
            [
                ['frontend_label' => [], 'frontend_input' => 'file', 'attribute_code' => 'correct_code'],
                false,
            ],
            [
                ['frontend_label' => [], 'frontend_input' => 'select', 'attribute_code' => 'inCorrect_code'],
                true
            ],
            [
                [
                    'frontend_label' => [],
                    'frontend_input' => 'select',
                    'attribute_code' => 'in!correct_code',
                ],
                true
            ]
        ];
    }

    /**
     * Test filter data provider
     *
     * @return array
     */
    public function getFilterDataProvider()
    {
        return [
            [
                [
                    'frontend_label' => ['<script></script>'],
                    'frontend_input' => 'file',
                    'attribute_code' => 'correct_code',
                ],
                ['frontend_label' => [''], 'frontend_input' => 'file', 'attribute_code' => 'correct_code'],
            ]
        ];
    }
}
