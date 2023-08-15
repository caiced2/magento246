<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Source;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Model\Source\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    /**
     * @var Type
     */
    protected $_model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->_model = $objectManager->getObject(Type::class);
    }

    public function testGetFlatColumns()
    {
        $abstractAttributeMock = $this->createPartialMock(
            AbstractAttribute::class,
            ['getAttributeCode']
        );
        $abstractAttributeMock->expects($this->any())->method('getAttributeCode')->willReturn('code');

        $this->_model->setAttribute($abstractAttributeMock);

        $flatColumns = $this->_model->getFlatColumns();

        $this->assertIsArray($flatColumns, 'FlatColumns must be an array value');
        $this->assertNotEmpty($flatColumns, 'FlatColumns must be not empty');
        foreach ($flatColumns as $result) {
            $this->assertArrayHasKey('unsigned', $result, 'FlatColumns must have "unsigned" column');
            $this->assertArrayHasKey('default', $result, 'FlatColumns must have "default" column');
            $this->assertArrayHasKey('extra', $result, 'FlatColumns must have "extra" column');
            $this->assertArrayHasKey('type', $result, 'FlatColumns must have "type" column');
            $this->assertArrayHasKey('nullable', $result, 'FlatColumns must have "nullable" column');
            $this->assertArrayHasKey('comment', $result, 'FlatColumns must have "comment" column');
        }
    }

    /**
     * @param int $value
     * @param string $result
     * @dataProvider getOptionTextDataProvider
     */
    public function testGetOptionText($value, $result)
    {
        $this->assertEquals($result, $this->_model->getOptionText($value));
    }

    /**
     * @return array
     */
    public function getOptionTextDataProvider()
    {
        return [
            [0, 'Virtual'],
            [1, 'Physical'],
            [2, 'Combined'],
            [3, null]
        ];
    }

    public function testGetAllOptions()
    {
        $result = [
            [
                'value' => 0,
                'label' => 'Virtual',
            ],
            [
                'value' => 1,
                'label' => 'Physical'
            ],
            [
                'value' => 2,
                'label' => 'Combined'
            ],
        ];

        $this->assertEquals($result, $this->_model->getAllOptions());
    }
}
