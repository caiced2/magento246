<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Model\Rules\Rule;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VisualMerchandiser\Model\Rules\Rule\Source as RuleSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SourceTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var RuleSource
     */
    private $model;

    /**
     * @var ProductCollection|MockObject
     */
    private $productCollectionMock;

    /**
     * @var Attribute|MockObject
     */
    private $attributeMock;

    /**
     * @var AbstractSource|MockObject
     */
    private $abstractSourceMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter'])
            ->getMock();

        $this->attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSource'])
            ->getMock();

        $this->abstractSourceMock = $this->getMockForAbstractClass(AbstractSource::class);
    }

    /**
     * @param array $attributeOptions
     * @param array $rule
     * @param array $expectedCondition
     * @dataProvider getAllOptionsDataProvider
     */
    public function testApplyToCollection(array $attributeOptions, array $rule, array $expectedCondition)
    {
        $this->model = $this->objectManagerHelper->getObject(
            RuleSource::class,
            [
                '_attribute' => $this->attributeMock,
                '_rule' => $rule
            ]
        );

        $this->attributeMock->expects($this->once())
            ->method('getSource')
            ->willReturn($this->abstractSourceMock);

        $this->abstractSourceMock->expects($this->once())
            ->method('getAllOptions')
            ->with(false, true)
            ->willReturn($attributeOptions);

        $this->productCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with($rule['attribute'], $expectedCondition)
            ->willReturnSelf();

        $this->model->applyToCollection($this->productCollectionMock);
    }

    /**
     * @return array
     */
    public function getAllOptionsDataProvider()
    {
        return [
            'Equal condition and option is not detected by id' => [
                'attributeOptions' => [
                    [
                        'value' => '16',
                        'label' => 'blck'
                    ],
                    [
                        'value' => '17',
                        'label' => 'wht'
                    ]
                ],
                'rule' => [
                    'attribute' => 'color',
                    'operator' => 'eq',
                    'value' => '16'
                ],
                'expectedCondition' => ['eq' => '16'],
            ],
            'Equal condition and option is not detected by label' => [
                'attributeOptions' => [
                    [
                        'value' => '16',
                        'label' => 'blck'
                    ],
                    [
                        'value' => '17',
                        'label' => 'wht'
                    ]
                ],
                'rule' => [
                    'attribute' => 'color',
                    'operator' => 'eq',
                    'value' => 'black'
                ],
                'expectedCondition' => ['eq' => 'black'],
            ],
            'Equal condition and option is detected by label' => [
                'attributeOptions' => [
                    [
                        'value' => '16',
                        'label' => 'black'
                    ],
                    [
                        'value' => '17',
                        'label' => 'wht'
                    ]
                ],
                'rule' => [
                    'attribute' => 'color',
                    'operator' => 'eq',
                    'value' => 'black'
                ],
                'expectedCondition' => ['eq' => '16'],
            ],
            'Contains condition' => [
                'attributeOptions' => [
                    [
                        'value' => '100',
                        'label' => '10 mm'
                    ],
                    [
                        'value' => '50',
                        'label' => '11 mm'
                    ],
                    [
                        'value' => '115',
                        'label' => '12 mm'
                    ],
                    [
                        'value' => '160',
                        'label' => '11.5 mm'
                    ]
                ],
                'rule' => [
                    'attribute' => 'diameter',
                    'operator' => 'like',
                    'value' => '11'
                ],
                'expectedCondition' => ['in' => [50, 160]],
            ]
        ];
    }
}
