<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftRegistry\Model\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    /**
     * @var Type
     */
    protected $model;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var MockObject
     */
    protected $resourceMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(\Magento\GiftRegistry\Model\ResourceModel\Type::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            Type::class,
            [
                'resource' => $this->resourceMock
            ]
        );
    }

    /**
     * @dataProvider getAttributesStoreDataDataProvider
     *
     * @param array $attributes
     * @param array $storeData
     * @param array $expectedResult
     */
    public function testGetAttributesStoreData($attributes, $storeData, $expectedResult)
    {
        $this->resourceMock->expects($this->any())->method('getAttributesStoreData')
            ->willReturn($storeData);
        $this->assertEquals($expectedResult, $this->model->getAttributesStoreData($attributes));
    }

    /**
     * @return array
     */
    public function getAttributesStoreDataDataProvider()
    {
        return [
            [
                'attributes' => [
                    'attribute_code1' => [
                        'label' => 'basic_attribute1_label',
                        'sort_order' => 10,
                        'options' => [
                            'option_code' => 'option1',
                        ]
                    ],
                    'attribute_code2' => [
                        'label' => 'basic_attribute2_label',
                        'sort_order' => 5,
                    ],
                ],
                'storeData' => [
                    ['attribute_code' => 'attribute_code1', 'label' => 'attribute_label1', 'option_code' => ''],
                    ['attribute_code' => 'attribute_code1', 'label' => 'option_label',  'option_code' => 'option_code']
                ],
                'expectedResult' => [
                    'attribute_code2' => [
                        'label' => 'basic_attribute2_label',
                        'sort_order' => 5,
                    ],
                    'attribute_code1' => [
                        'label' => 'attribute_label1',
                        'default_label' => 'basic_attribute1_label',
                        'sort_order' => 10,
                        'options' => [
                            [
                                'code' => 'option_code',
                                'label' => 'option_label',
                                'default_label' => 'option1'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
