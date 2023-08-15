<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Condition\Combine;

use Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine;
use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class AbstractCombineTest extends TestCase
{
    /**
     * @var AbstractCombine
     */
    protected $model;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var Segment
     */
    protected $resourceSegment;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->createMock(ConditionFactory::class);
        $this->resourceSegment =
            $this->createMock(Segment::class);

        $this->model = $this->getMockForAbstractClass(
            AbstractCombine::class,
            [
                $this->context,
                $this->conditionFactory,
                $this->resourceSegment,
            ]
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->context,
            $this->conditionFactory,
            $this->resourceSegment
        );
    }

    public function testGetMatchedEvents()
    {
        $result = $this->model->getMatchedEvents();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetDefaultOperatorInputByType()
    {
        $result = $this->model->getDefaultOperatorInputByType();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('string', $result);
        $this->assertIsArray($result['string']);
        $this->assertEquals(['==', '!=', '{}', '!{}'], $result['string']);

        $this->assertArrayHasKey('numeric', $result);
        $this->assertIsArray($result['numeric']);
        $this->assertEquals(['==', '!=', '>=', '>', '<=', '<'], $result['numeric']);

        $this->assertArrayHasKey('date', $result);
        $this->assertIsArray($result['date']);
        $this->assertEquals(['==', '>=', '<='], $result['date']);

        $this->assertArrayHasKey('select', $result);
        $this->assertIsArray($result['select']);
        $this->assertEquals(['==', '!=', '<=>'], $result['select']);

        $this->assertArrayHasKey('boolean', $result);
        $this->assertIsArray($result['boolean']);
        $this->assertEquals(['==', '!=', '<=>'], $result['boolean']);

        $this->assertArrayHasKey('multiselect', $result);
        $this->assertIsArray($result['multiselect']);
        $this->assertEquals(['{}', '!{}', '()', '!()'], $result['multiselect']);

        $this->assertArrayHasKey('grid', $result);
        $this->assertIsArray($result['grid']);
        $this->assertEquals(['()', '!()'], $result['grid']);
    }

    public function testLoadArray()
    {
        $data = [
            'operator' => 'test_operator',
            'attribute' => 'test_attribute',
        ];

        $result = $this->model->loadArray($data);

        $this->assertEquals($data['operator'], $result->getOperator());
        $this->assertEquals($data['attribute'], $result->getAttribute());
    }

    public function testGetResource()
    {
        $result = $this->model->getResource();

        $this->assertInstanceOf(Segment::class, $result);
        $this->assertEquals($this->resourceSegment, $result);
    }

    public function testGetIsRequired()
    {
        $this->model->setValue(1);

        $this->assertTrue($this->model->getIsRequired());

        $this->model->setValue(0);

        $this->assertFalse($this->model->getIsRequired());
    }

    public function testGetCombineProductCondition()
    {
        $this->assertFalse($this->model->getCombineProductCondition());
    }
}
