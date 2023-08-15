<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\TargetRule\Model\Rule\Condition\Combine;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombineTest extends TestCase
{
    /**
     * Combine model
     *
     * @var \Magento\TargetRule\Model\Rule\Condition\Combine
     */
    protected $_combine;

    /**
     * Return array
     *
     * @var array
     */
    protected $returnArray = [
        'value' => 'Test',
        'label' => 'Test Conditions',
    ];

    protected function setUp(): void
    {
        $attribute = $this->createPartialMock(
            Attributes::class,
            ['getNewChildSelectOptions']
        );

        $attribute->expects($this->any())
            ->method('getNewChildSelectOptions')
            ->willReturn($this->returnArray);

        $attributesFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule\Condition\Product\AttributesFactory::class,
            ['create']
        );

        $attributesFactory->expects($this->any())
            ->method('create')
            ->willReturn($attribute);

        $this->_combine = (new ObjectManager($this))->getObject(
            Combine::class,
            [
                'context' => $this->_getCleanMock(Context::class),
                'attributesFactory' => $attributesFactory,
            ]
        );
    }

    /**
     * Get clean mock by class name
     *
     * @param string $className
     * @return MockObject
     */
    protected function _getCleanMock($className)
    {
        return $this->createMock($className);
    }

    public function testGetNewChildSelectOptions()
    {
        $result = [
            '0' => [
                'value' => '',
                'label' => 'Please choose a condition to add.',
            ],
            '1' => [
                'value' => Combine::class,
                'label' => 'Conditions Combination',
            ],
            '2' => $this->returnArray,
        ];

        $this->assertEquals($result, $this->_combine->getNewChildSelectOptions());
    }

    public function testCollectValidatedAttributes()
    {
        $productCollection = $this->_getCleanMock(Collection::class);
        $condition = $this->_getCleanMock(Combine::class);

        $condition->expects($this->once())
            ->method('collectValidatedAttributes')->willReturnSelf();

        $this->_combine->setConditions([$condition]);

        $this->assertEquals($this->_combine, $this->_combine->collectValidatedAttributes($productCollection));
    }
}
