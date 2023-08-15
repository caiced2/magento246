<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleTest extends TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Rule
     */
    protected $_rule;

    /**
     * SQL Builder mock
     *
     * @var \Magento\Rule\Model\Condition\Sql\Builder|MockObject
     */
    protected $_sqlBuilderMock;

    /**
     * Product factory mock
     *
     * @var \Magento\Catalog\Model\ProductFactory|MockObject
     */
    protected $_productFactory;

    /**
     * Rule factory mock
     *
     * @var \Magento\TargetRule\Model\Rule\Condition\CombineFactory|MockObject
     */
    protected $_ruleFactory;

    /**
     * Action factory mock
     *
     * @var \Magento\TargetRule\Model\Actions\Condition\CombineFactory|MockObject
     */
    protected $_actionFactory;

    /**
     * Locale date mock
     *
     * @var TimezoneInterface|MockObject
     */
    protected $_localeDate;

    protected function setUp(): void
    {
        $this->_sqlBuilderMock = $this->_getCleanMock(Builder::class);

        $this->_productFactory = $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']);

        $this->_ruleFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule\Condition\CombineFactory::class,
            ['create']
        );

        $this->_actionFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\Actions\Condition\CombineFactory::class,
            ['create']
        );

        $this->_localeDate = $this->getMockForAbstractClass(
            TimezoneInterface::class,
            ['isScopeDateInInterval'],
            '',
            false
        );

        $objects = [
            [
                ExtensionAttributesFactory::class,
                $this->createMock(ExtensionAttributesFactory::class)
            ],
            [
                AttributeValueFactory::class,
                $this->createMock(AttributeValueFactory::class)
            ],
            [
                Json::class,
                $this->getSerializerMock()
            ]
        ];
        $this->prepareObjectManager($objects);

        $this->_rule = (new ObjectManager($this))->getObject(
            Rule::class,
            [
                'context' => $this->_getCleanMock(Context::class),
                'registry' => $this->_getCleanMock(Registry::class),
                'formFactory' => $this->_getCleanMock(FormFactory::class),
                'localeDate' => $this->_localeDate,
                'ruleFactory' => $this->_ruleFactory,
                'actionFactory' => $this->_actionFactory,
                'productFactory' => $this->_productFactory,
                'ruleProductIndexerProcessor' => $this->_getCleanMock(
                    Processor::class
                ),
                'sqlBuilder' => $this->_sqlBuilderMock,
            ]
        );
    }

    /**
     * Get mock for serializer
     *
     * @return Json|MockObject
     */
    private function getSerializerMock()
    {
        $serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        return $serializerMock;
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

    public function testDataHasChangedForAny()
    {
        $fields = ['first', 'second'];
        $this->assertFalse($this->_rule->dataHasChangedForAny($fields));

        $fields = ['first', 'second'];
        $this->_rule->setData('first', 'test data');
        $this->_rule->setOrigData('first', 'origin test data');
        $this->assertTrue($this->_rule->dataHasChangedForAny($fields));
    }

    public function testGetConditionsInstance()
    {
        $this->_ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn(true);

        $this->assertTrue($this->_rule->getConditionsInstance());
    }

    public function testGetActionsInstance()
    {
        $this->_actionFactory->expects($this->once())
            ->method('create')
            ->willReturn(true);

        $this->assertTrue($this->_rule->getActionsInstance());
    }

    public function testGetAppliesToOptions()
    {
        $result[Rule::RELATED_PRODUCTS] = __('Related Products');
        $result[Rule::UP_SELLS] = __('Up-sells');
        $result[Rule::CROSS_SELLS] = __('Cross-sells');

        $this->assertEquals($result, $this->_rule->getAppliesToOptions());

        $result[''] = __('-- Please Select --');

        $this->assertEquals($result, $this->_rule->getAppliesToOptions('test'));
    }

    public function testPrepareMatchingProducts()
    {
        $productCollection = $this->_getCleanMock(Collection::class);

        $productMock = $this->createPartialMock(
            Product::class,
            ['getCollection', '__sleep', '__wakeup', 'load', 'getId']
        );

        $productMock->method('getId')
            ->willReturn(1);

        $productMock->expects($this->once())
            ->method('getCollection')
            ->willReturn($productCollection);

        $productMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->_productFactory->expects($this->any())
            ->method('create')
            ->willReturn($productMock);

        $productCollection->expects($this->once())
            ->method('getLastPageNumber')
            ->willReturn(1);

        $iterator = new \ArrayIterator([$productMock]);
        $productCollection->method('getIterator')
            ->willReturn($iterator);

        /**
         * @var Combine|MockObject $conditions
         */
        $conditions = $this->getMockBuilder(Combine::class)
            ->addMethods(['collectValidatedAttributes', 'getConditionForCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $conditions->expects($this->once())
            ->method('collectValidatedAttributes')
            ->with($productCollection);

        $conditions->expects($this->once())
            ->method('getConditionForCollection')
            ->with($productCollection);

        $this->_rule->setConditions($conditions);
        $this->_rule->prepareMatchingProducts();
        $this->assertEquals([1], $this->_rule->getMatchingProductIds());
    }

    public function testCheckDateForStore()
    {
        $storeId = 1;
        $this->_localeDate->expects($this->once())
            ->method('isScopeDateInInterval')
            ->willReturn(true);
        $this->assertTrue($this->_rule->checkDateForStore($storeId));
    }

    public function testGetPositionsLimit()
    {
        $this->assertEquals(20, $this->_rule->getPositionsLimit());

        $this->_rule->setData('positions_limit', 10);
        $this->assertEquals(10, $this->_rule->getPositionsLimit());
    }

    public function testGetActionSelectBind()
    {
        $this->assertNull($this->_rule->getActionSelectBind());

        $result = [1 => 'test'];
        $this->_rule->setData('action_select_bind', json_encode($result));
        $this->assertEquals($result, $this->_rule->getActionSelectBind());

        $this->_rule->setActionSelectBind($result);
        $this->assertEquals($result, $this->_rule->getActionSelectBind());
    }

    public function testValidateData()
    {
        $object = $this->_getCleanMock(DataObject::class);
        $this->assertTrue($this->_rule->validateData($object));

        $object = $this->createPartialMock(DataObject::class, ['getData']);
        $array['actions'] = [1 => 'test'];

        $object->expects($this->once())
            ->method('getData')
            ->willReturn($array);

        $this->assertTrue($this->_rule->validateData($object));

        $object = $this->createPartialMock(DataObject::class, ['getData']);
        $array['actions'] = [2 => ['type' => DataObject::class, 'attribute' => 'test attribute']];

        $object->expects($this->once())
            ->method('getData')
            ->willReturn($array);

        $result = [ 0 => __(
            'This attribute code is invalid. Please use only letters (a-z), numbers (0-9) or underscores (_),'
            . ' and be sure the code begins with a letter.'
        )];
        $this->assertEquals($result, $this->_rule->validateData($object));
    }

    public function testValidateDataWithException()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('The attribute\'s model class name is invalid. Verify the name and try again.');
        $object = $this->createPartialMock(DataObject::class, ['getData']);
        $array['actions'] = [2 => ['type' => 'test type', 'attribute' => 'test attribute']];

        $object->expects($this->once())
            ->method('getData')
            ->willReturn($array);

        $this->_rule->validateData($object);
    }

    public function testValidateByEntityId()
    {
        $combine = $this->getMockBuilder(Combine::class)
            ->addMethods(['setRule', 'setId', 'setPrefix'])
            ->disableOriginalConstructor()
            ->getMock();

        $combine->expects($this->any())
            ->method('setRule')->willReturnSelf();

        $combine->expects($this->any())
            ->method('setId')->willReturnSelf();

        $this->_ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($combine);

        $this->assertTrue($this->_rule->validateByEntityId(1));
    }

    /**
     * @param $map
     */
    private function prepareObjectManager($map)
    {
        $objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->setMethods(['getInstance'])
            ->getMockForAbstractClass();
        $objectManagerMock->expects($this->any())->method('getInstance')->willReturnSelf();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
        $reflectionClass = new \ReflectionClass(\Magento\Framework\App\ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);
    }
}
