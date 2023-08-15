<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Reward;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Reward\History;
use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
    /**
     * @var History
     */
    protected $_model;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->_model = $objectManagerHelper->getObject(History::class);
    }

    public function testGetAdditionalDataEmpty()
    {
        $this->assertSame([], $this->_model->getAdditionalData());
    }

    public function testGetAdditionalDataNotEmpty()
    {
        $value = ['field1' => 'value1', 'field2' => 'value2'];
        $this->_model->setData('additional_data', $value);
        $this->assertEquals($value, $this->_model->getAdditionalData());
    }

    public function testGetAdditionalDataInvalid()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Additional data for a reward point history has to be an array');
        $this->_model->setData('additional_data', 'not an array');
        $this->_model->getAdditionalData();
    }

    /**
     * @param string $inputKey
     * @param string $expectedResult
     * @dataProvider getAdditionalDataByKeyDataProvider
     */
    public function testGetAdditionalDataByKey($inputKey, $expectedResult)
    {
        $this->_model->setData('additional_data', ['field' => 'value']);
        $this->assertSame($expectedResult, $this->_model->getAdditionalDataByKey($inputKey));
    }

    public function getAdditionalDataByKeyDataProvider()
    {
        return ['existing field' => ['field', 'value'], 'unknown field' => ['unknown', null]];
    }

    /**
     * @param array $inputData
     * @param array $expectedResult
     * @dataProvider getAdditionalDataDataProvider
     */
    public function testAddAdditionalData(array $inputData, array $expectedResult)
    {
        $this->_model->setData('additional_data', ['field1' => 'value1', 'field2' => 'value2']);
        $this->_model->addAdditionalData($inputData);
        $this->assertEquals($expectedResult, $this->_model->getAdditionalData());
    }

    public function getAdditionalDataDataProvider()
    {
        return [
            'adding new field' => [
                ['field3' => 'value3'],
                ['field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3'],
            ],
            'overriding existing field' => [
                ['field1' => 'overridden_value'],
                ['field1' => 'overridden_value', 'field2' => 'value2'],
            ]
        ];
    }
}
