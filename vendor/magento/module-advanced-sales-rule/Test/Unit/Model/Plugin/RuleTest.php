<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Plugin;

use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor;
use Magento\AdvancedSalesRule\Model\Plugin\Rule;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Combine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    /**
     * @var Processor|MockObject
     */
    protected $indexerProcessorMock;

    /**
     * @var Rule
     */
    protected $model;

    /**
     * @var \Closure
     */
    protected $closureMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $className = Processor::class;
        $this->indexerProcessorMock = $this->createMock($className);
        $serializer = $this->getMockBuilder(Json::class)
            ->onlyMethods(['serialize'])
            ->disableOriginalConstructor()
            ->getMock();

        $serializer->method('serialize')
            ->willReturnCallback(
                function ($data) {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    return json_encode($data);
                }
            );

        $this->model = $this->objectManager->getObject(
            Rule::class,
            [
                'indexerProcessor' => $this->indexerProcessorMock,
                'serializer' => $serializer,
            ]
        );
    }

    /**
     * test AroundSave when the sales rule is a new object
     */
    public function testAroundSaveNewObject()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $subject->expects($this->any())
            ->method('isObjectNew')
            ->willReturn(true);

        $subject->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->indexerProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when skip_save_filter flag is set
     */
    public function testAroundSaveSkipAfter()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $subject->expects($this->once())
            ->method('getData')
            ->with('skip_save_filter')
            ->willReturn(true);

        $subject->expects($this->never())
            ->method('isObjectNew');

        $this->indexerProcessorMock->expects($this->never())
            ->method('reindexRow');

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave
     */
    public function testAfterSave()
    {
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject =$this->createMock(\Magento\SalesRule\Model\Rule::class);

        $subject->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        /** @var  FilterableConditionInterface $conditions */
        $conditions = $this->getMockBuilder(FilterableConditionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFilterable', 'getFilterGroups'])
            ->addMethods(['asArray'])
            ->getMockForAbstractClass();
        $subject->expects($this->any())
            ->method('getConditions')
            ->willReturn($conditions);

        $conditions->expects($this->once())
            ->method('asArray')
            ->willReturn(['a' => 'b']);

        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $subject->expects($this->once())
            ->method('getOrigData')
            ->with('conditions_serialized')
            ->willReturn('abc');

        $this->indexerProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when force_save_filter flag is set
     */
    public function testAfterSaveForced()
    {
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject =$this->createMock(\Magento\SalesRule\Model\Rule::class);

        $subject->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        /** @var  FilterableConditionInterface $conditions */
        $conditions = $this->getMockBuilder(FilterableConditionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFilterable', 'getFilterGroups'])
            ->addMethods(['asArray'])
            ->getMockForAbstractClass();
        $subject->expects($this->any())
            ->method('getConditions')
            ->willReturn($conditions);

        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);

        $subject->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnCallback(
                function ($field) {
                    if ($field == 'skip_save_filter') {
                        return false;
                    } elseif ($field == 'force_save_filter') {
                        return true;
                    } else {
                        return true; // default
                    }
                }
            );

        $this->indexerProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when condition did not change
     */
    public function testAfterSaveConditionNotChanged()
    {
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject =$this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->addMethods(['getConditionsSerialized'])
            ->onlyMethods(['getOrigData', 'isObjectNew'])
            ->getMock();

        $serializedCondition = 'serialized';
        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $subject->expects($this->once())
            ->method('getConditionsSerialized')
            ->willReturn($serializedCondition);
        $subject->expects($this->once())
            ->method('getOrigData')
            ->with('conditions_serialized')
            ->willReturn($serializedCondition);

        $this->indexerProcessorMock->expects($this->never())
            ->method('reindexRow');

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when condition did not change
     */
    public function testAfterSaveConditionNotChangedNoSerializedCondition()
    {
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->addMethods(['getConditionsSerialized'])
            ->onlyMethods(['getOrigData', 'isObjectNew', 'getConditions'])
            ->getMock();

        $this->setupConditionNotChanged($subject);

        $this->indexerProcessorMock->expects($this->never())
            ->method('reindexRow');

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * Setup method for testAfterSaveConditionNotChangedNoSerializedCondition
     * @param \Magento\SalesRule\Model\Rule|MockObject $subject
     */
    protected function setupConditionNotChanged($subject)
    {
        $conditionArray = ['a' => 'b'];
        $originalConditionArray = ['a' => 'b'];
        $conditionMock = $this->getMockBuilder(Combine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['asArray'])
            ->getMock();
        $conditionMock->expects($this->once())
            ->method('asArray')
            ->willReturn($conditionArray);
        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $subject->expects($this->once())
            ->method('getConditionsSerialized')
            ->willReturn(null);
        $subject->expects($this->once())
            ->method('getOrigData')
            ->with('conditions_serialized')
            ->willReturn(json_encode($originalConditionArray));
        $subject->expects($this->once())
            ->method('getConditions')
            ->willReturn($conditionMock);
    }
}
