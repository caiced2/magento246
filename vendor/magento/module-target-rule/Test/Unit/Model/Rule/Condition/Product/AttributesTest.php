<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition\Product;

use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Rule\Condition\Product\Attributes
     */
    protected $_attributes;

    protected function setUp(): void
    {
        $productResource = $this->getMockBuilder(Product::class)
            ->addMethods(['loadValueOptions'])
            ->onlyMethods(['loadAllAttributes'])
            ->disableOriginalConstructor()
            ->getMock();

        $productResource->expects($this->any())
            ->method('loadAllAttributes')->willReturnSelf();

        $productResource->expects($this->any())
            ->method('loadValueOptions')->willReturnSelf();

        $this->_attributes = (new ObjectManager($this))->getObject(
            Attributes::class,
            [
                'context' => $this->_getCleanMock(Context::class),
                'backendData' => $this->_getCleanMock(Data::class),
                'config' => $this->_getCleanMock(Config::class),
                'productFactory' => $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']),
                'productResource' => $productResource,
                'attrSetCollection' => $this->_getCleanMock(
                    Collection::class
                ),
                'localeFormat' => $this->_getCleanMock(FormatInterface::class),
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
        $conditions = [
            [
                'value' => 'Magento\TargetRule\Model\Rule\Condition\Product\Attributes|attribute_set_id',
                'label' => __('Attribute Set'),
            ],
            [
                'value' => 'Magento\TargetRule\Model\Rule\Condition\Product\Attributes|category_ids',
                'label' => __('Category'),
            ],
        ];
        $result = ['value' => $conditions, 'label' => __('Product Attributes')];

        $this->assertEquals($result, $this->_attributes->getNewChildSelectOptions());
    }
}
